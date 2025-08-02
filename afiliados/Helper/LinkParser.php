<?php

namespace hardMOB\Afiliados\Helper;

class LinkParser
{
    protected $app;

    public function __construct(\XF\App $app)
    {
        $this->app = $app;
    }

    public function extractProductInfo($url)
    {
        $info = [
            'domain' => null,
            'product_id' => null,
            'type' => 'unknown'
        ];

        // Handle invalid input types
        if (!is_string($url) || empty($url)) {
            return $info;
        }

        // Parse da URL
        $parsed = parse_url($url);
        if (!$parsed || !isset($parsed['host'])) {
            return $info;
        }

        $info['domain'] = $parsed['host'];

        // Amazon
        if (strpos($info['domain'], 'amazon.') !== false) {
            $info['type'] = 'amazon';
            if (preg_match('/\/dp\/([A-Z0-9]{10})/', $url, $matches)) {
                $info['product_id'] = $matches[1];
            }
        }
        
        // MercadoLivre
        elseif (strpos($info['domain'], 'mercadolivre.') !== false) {
            $info['type'] = 'mercadolivre';
            if (preg_match('/(MLB-\d+)/', $url, $matches)) {
                $info['product_id'] = $matches[1];
            }
        }
        
        // Shopee
        elseif (strpos($info['domain'], 'shopee.') !== false) {
            $info['type'] = 'shopee';
            if (preg_match('/\/([^\/]+)-i\.(\d+)\.(\d+)/', $url, $matches)) {
                $info['product_id'] = $matches[0]; // Slug completo
            }
        }

        return $info;
    }

    public function isAffiliateUrl($url)
    {
        // Verifica se a URL já contém parâmetros de afiliado conhecidos
        $affiliateParams = [
            'tag=', 'affiliate_id=', 'ref=', 'utm_source=',
            'partner=', 'aff_id=', 'affid=', 'affiliate='
        ];

        foreach ($affiliateParams as $param) {
            if (strpos($url, $param) !== false) {
                return true;
            }
        }

        return false;
    }

    public function cleanUrl($url)
    {
        // Remove parâmetros de tracking comuns mas mantém parâmetros essenciais
        $parsed = parse_url($url);
        
        if (!$parsed) {
            return $url;
        }

        $cleanUrl = $parsed['scheme'] . '://' . $parsed['host'];
        
        if (isset($parsed['path'])) {
            $cleanUrl .= $parsed['path'];
        }

        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $params);
            
            // Remove parâmetros de tracking
            $trackingParams = [
                'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
                'ref', 'tag', 'affiliate_id', 'aff_id', 'affid', 'fbclid', 'gclid'
            ];
            
            foreach ($trackingParams as $param) {
                unset($params[$param]);
            }
            
            if (!empty($params)) {
                $cleanUrl .= '?' . http_build_query($params);
            }
        }

        return $cleanUrl;
    }

    public function validateUrl($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $parsed = parse_url($url);
        
        // Verifica se é HTTP/HTTPS
        if (!in_array($parsed['scheme'], ['http', 'https'])) {
            return false;
        }

        // Verifica se tem host válido
        if (empty($parsed['host'])) {
            return false;
        }

        return true;
    }
}
