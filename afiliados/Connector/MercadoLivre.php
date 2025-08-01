<?php

namespace hardMOB\Afiliados\Connector;

class MercadoLivre implements StoreInterface
{
    protected $store;

    public function __construct(\hardMOB\Afiliados\Entity\Store $store)
    {
        $this->store = $store;
    }

    public function generateAffiliateUrl($slug)
    {
        // Formato MercadoLivre: https://produto.mercadolivre.com.br/MLB-ID ou direto com ?utm_source
        $baseUrl = 'https://' . $this->store->domain;
        
        if (strpos($slug, 'MLB-') === 0) {
            $productUrl = $baseUrl . '/p/' . $slug;
        } else {
            $productUrl = $baseUrl . $slug;
        }

        $separator = strpos($productUrl, '?') !== false ? '&' : '?';
        return $productUrl . $separator . 'utm_source=' . $this->store->affiliate_code;
    }

    public function validateSlug($slug)
    {
        // Valida IDs do MercadoLivre (MLB-XXXXXXXXX)
        if (preg_match('/^MLB-\d+$/', $slug)) {
            return true;
        }

        // Valida URLs completas
        if (strpos($slug, '/p/') !== false || strpos($slug, 'mercadolivre.com') !== false) {
            return true;
        }

        return false;
    }
}
