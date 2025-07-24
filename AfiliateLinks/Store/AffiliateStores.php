<?php

namespace hardMOB\AfiliateLinks\Store;

use XF\Service\AbstractService;

class AffiliateStores extends AbstractService
{
    protected $cacheKey = 'hmaf_stores_map';
    protected $cacheTime = 86400; // 24 horas
    
    /**
     * Retorna o mapa de lojas e parâmetros de afiliado
     */
    public function getStoresMap(): array
    {
        $cache = $this->app()->cache('simple');
        $map = $cache->fetch($this->cacheKey);
        
        if ($map === null) {
            $map = $this->buildStoresMap();
            $cache->save($this->cacheKey, $map, $this->cacheTime);
        }
        
        return $map;
    }
    
    /**
     * Constrói o mapa de lojas a partir das configurações
     */
    public function buildStoresMap(): array
    {
        $map = [];
        $options = $this->app()->options();
        
        if (empty($options->hmaf_stores_list)) {
            return $map;
        }
        
        foreach (preg_split('/[\r\n]+/', trim($options->hmaf_stores_list)) as $line) {
            $line = trim($line);
            if (!$line || strpos($line, '|') === false) {
                continue;
            }
            
            list($dom, $param) = array_map('trim', explode('|', $line, 2));
            $dom = preg_replace('#^www\.#i', '', strtolower($dom));
            $param = $this->sanitizeAffiliateParam($param);
            
            $map[$dom] = $param;
        }
        
        return $map;
    }
    
    /**
     * Limpa o cache de mapeamentos
     */
    public function clearCache(): void
    {
        $this->app()->cache('simple')->delete($this->cacheKey);
    }
    
    /**
     * Sanitiza um parâmetro de afiliado
     */
    protected function sanitizeAffiliateParam(string $param): string
    {
        // Remove caracteres potencialmente perigosos
        $param = trim($param);
        $param = str_replace(['"', "'", '<', '>', '`'], '', $param);
        
        // Permite apenas caracteres comuns de querystring
        $param = preg_replace('/[^a-zA-Z0-9\-\_\.\=\&\%\:\,\/\?\#]+/', '', $param);
        
        return $param;
    }
}