<?php

namespace hardMOB\AfiliateLinks\Handler;

use XF\Service\AbstractService;

class AliExpressHandler extends AbstractService
{
    protected $cacheDir;
    protected $cacheLimit = 10000;
    
    /**
     * Processa URL do AliExpress
     * @version 1.0.0 (2025-07-22)
     */
    public function processUrl(string $url, string $param): string
    {
        // Não usamos o parâmetro passado pois vamos usar API ao invés de strings estáticas
        
        // Verifica se o AliExpress está ativado nas configurações
        if (!$this->getAliExpressConfig('enabled')) {
            return $url;
        }
        
        // Normaliza a URL antes de processar
        $normalizedUrl = $this->normalizeAliExpressUrl($url);
        
        // Se a URL não for válida ou não for do AliExpress, retorna a URL original
        if ($normalizedUrl === $url) {
            return $url;
        }
        
        // Inicializa o diretório de cache
        $this->initCacheDirectory();
        
        // Limita o número de arquivos no cache
        $this->limitCacheFiles();
        
        // Chama API para obter o link de afiliado
        $affiliateUrl = $this->getAffiliateLink($normalizedUrl);
        
        return $affiliateUrl;
    }
    
    /**
     * Normaliza a URL do AliExpress removendo parâmetros desnecessários
     */
    protected function normalizeAliExpressUrl(string $url): string
    {
        // Parse a URL para extrair o host e path
        $parts = parse_url($url);

        if (!isset($parts['host'], $parts['path'])) {
            return $url;
        }

        $host = strtolower($parts['host']);
        // Verifica se é um domínio válido do AliExpress (inclui subdomínios)
        if (!preg_match('#(?:^|\.)aliexpress\.com$#', $host)) {
            return $url;
        }

        // Extrai até .html no path para itens padrão
        if (preg_match('#^(/item/\d+\.html)#', $parts['path'], $m)) {
            // Reconstrói a URL limpa
            return $parts['scheme'] . '://' . $host . $m[1];
        }
        
        // Links móveis com coin-index
        if ($host === 'm.aliexpress.com' && strpos($parts['path'], 'coin-index') !== false) {
            if (isset($parts['query']) && preg_match('#productIds=(\d+)#', $parts['query'], $pidMatch)) {
                $productId = $pidMatch[1];
                return 'https://m.aliexpress.com/p/coin-index/index.html?productIds=' . $productId;
            }
        }

        return $url;
    }
    
    /**
     * Obtém o link de afiliado usando a API do AliExpress
     */
    protected function getAffiliateLink(string $origUrl): string
    {
        $appKey = $this->getAliExpressConfig('app_key');
        $appSecret = $this->getAliExpressConfig('app_secret');
        $trackingId = $this->getAliExpressConfig('tracking_id');
        
        if (!$appKey || !$appSecret || !$trackingId) {
            return $origUrl;
        }
        
        // Verifica o cache
        $hash = md5($origUrl);
        $cacheFile = $this->cacheDir . "/{$hash}.cache";

        // Retorna do cache se existir
        if (file_exists($cacheFile)) {
            return trim(file_get_contents($cacheFile));
        }
        
        // Monta chamada para Portals API (POST)
        $endpoint = 'https://api-sg.aliexpress.com/sync';
        $method = 'aliexpress.affiliate.link.generate';
        $params = [
            'app_key' => $appKey,
            'method' => $method,
            'source_values' => $origUrl,
            'tracking_id' => $trackingId,
            'promotion_link_type' => '0',
            'timestamp' => gmdate('Y-m-d H:i:s'),
            'sign_method' => 'md5',
            'format' => 'json',
        ];
        $params['sign'] = $this->signMd5($params, $appSecret);
        $url = $endpoint . '?method=' . $method . '&' . http_build_query($params);

        // Executa cURL com segurança
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        $resp = curl_exec($ch);
        $curlErr = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Log apenas em caso de erro
        if ($curlErr || $httpCode !== 200 || empty($resp)) {
            \XF::logError("AliExpress API error: HTTP $httpCode, CURL $curlErr, resp: " . substr($resp, 0, 300));
            return $origUrl;
        }

        // Decodifica JSON e procura promotion_link
        $json = @json_decode($resp, true);
        $links = $json['aliexpress_affiliate_link_generate_response']['resp_result']['result']['promotion_links']['promotion_link'] ?? [];

        foreach ($links as $link) {
            if ($link['source_value'] === $origUrl && !empty($link['promotion_link'])) {
                file_put_contents($cacheFile, $link['promotion_link']);
                return $link['promotion_link'];
            }
        }

        // Fallback
        return $origUrl;
    }
    
    /**
     * Assina os parâmetros via MD5 conforme Portals 2
     */
    protected function signMd5(array $params, string $appSecret): string
    {
        ksort($params);
        $base = $appSecret;
        foreach ($params as $k => $v) {
            if ($k === 'sign' || $v === '') continue;
            $base .= $k . $v;
        }
        $base .= $appSecret;
        return strtoupper(md5($base));
    }
    
    /**
     * Inicializa o diretório de cache
     */
    protected function initCacheDirectory(): void
    {
        if (empty($this->cacheDir)) {
            $this->cacheDir = \XF::getRootDirectory() . '/data/ae_cache';
            if (!is_dir($this->cacheDir)) {
                @mkdir($this->cacheDir, 0755, true);
            }
        }
    }
    
    /**
     * Limita o número de arquivos no cache
     */
    protected function limitCacheFiles(): void
    {
        $arquivos = glob($this->cacheDir . '/*.cache');
        if (count($arquivos) <= $this->cacheLimit) {
            return;
        }

        usort($arquivos, function($a, $b) {
            return filemtime($b) <=> filemtime($a);
        });
        $remover = array_slice($arquivos, $this->cacheLimit);
        foreach ($remover as $arquivo) {
            @unlink($arquivo);
        }
    }
    
    /**
     * Obtém uma configuração específica do AliExpress
     */
    protected function getAliExpressConfig(string $key)
    {
        $options = $this->app()->options();
        
        switch ($key) {
            case 'enabled':
                return !empty($options->hmaf_aliexpress_enabled);
            case 'app_key':
                return $options->hmaf_aliexpress_app_key ?? '';
            case 'app_secret':
                return $options->hmaf_aliexpress_app_secret ?? '';
            case 'tracking_id':
                return $options->hmaf_aliexpress_tracking_id ?? '';
            default:
                return null;
        }
    }
}