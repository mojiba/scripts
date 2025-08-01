<?php

namespace hardMOB\Afiliados\Connector;

class CustomStore implements StoreInterface
{
    protected $store;

    public function __construct(\hardMOB\Afiliados\Entity\Store $store)
    {
        $this->store = $store;
    }

    public function generateAffiliateUrl($slug)
    {
        // Implementação genérica para lojas customizadas
        $baseUrl = 'https://' . $this->store->domain;
        
        if (strpos($slug, 'http') === 0) {
            $productUrl = $slug;
        } else {
            $productUrl = $baseUrl . '/' . ltrim($slug, '/');
        }

        // Tenta diferentes formatos de parâmetro de afiliado
        $separator = strpos($productUrl, '?') !== false ? '&' : '?';
        
        // Verifica se já tem parâmetros de afiliado
        if (strpos($productUrl, 'ref=') !== false || 
            strpos($productUrl, 'affiliate') !== false ||
            strpos($productUrl, 'partner') !== false) {
            return $productUrl;
        }

        return $productUrl . $separator . 'ref=' . $this->store->affiliate_code;
    }

    public function validateSlug($slug)
    {
        // Validação básica - não vazio e formato mínimo de URL ou path
        if (empty($slug)) {
            return false;
        }

        // Aceita URLs completas ou paths relativos
        return (filter_var($slug, FILTER_VALIDATE_URL) !== false) || 
               (strpos($slug, '/') !== false) ||
               (strlen($slug) > 3);
    }
}
