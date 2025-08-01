<?php

namespace hardMOB\Afiliados\Connector;

class Amazon implements StoreInterface
{
    protected $store;

    public function __construct(\hardMOB\Afiliados\Entity\Store $store)
    {
        $this->store = $store;
    }

    public function generateAffiliateUrl($slug)
    {
        // Formato Amazon: https://amazon.com.br/dp/PRODUTO?tag=AFILIADO
        $baseUrl = 'https://' . $this->store->domain;
        
        // Se o slug já contém /dp/, usa diretamente
        if (strpos($slug, '/dp/') !== false || strpos($slug, '/product/') !== false) {
            $productUrl = $baseUrl . $slug;
        } else {
            // Assume que é um ASIN
            $productUrl = $baseUrl . '/dp/' . $slug;
        }

        return $productUrl . (strpos($productUrl, '?') !== false ? '&' : '?') . 'tag=' . $this->store->affiliate_code;
    }

    public function validateSlug($slug)
    {
        // Valida ASINs da Amazon (10 caracteres alfanuméricos)
        if (preg_match('/^[A-Z0-9]{10}$/', $slug)) {
            return true;
        }

        // Valida URLs completas da Amazon
        if (strpos($slug, '/dp/') !== false || strpos($slug, '/product/') !== false) {
            return true;
        }

        return false;
    }
}
