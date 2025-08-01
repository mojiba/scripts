<?php

namespace hardMOB\Afiliados\Connector;

class Shopee implements StoreInterface
{
    protected $store;

    public function __construct(\hardMOB\Afiliados\Entity\Store $store)
    {
        $this->store = $store;
    }

    public function generateAffiliateUrl($slug)
    {
        // Formato Shopee: https://shopee.com.br/produto-i.SHOP_ID.ITEM_ID?affiliate_id=AFILIADO
        $baseUrl = 'https://' . $this->store->domain;
        
        if (strpos($slug, '-i.') !== false) {
            $productUrl = $baseUrl . '/' . $slug;
        } else {
            $productUrl = $baseUrl . $slug;
        }

        $separator = strpos($productUrl, '?') !== false ? '&' : '?';
        return $productUrl . $separator . 'affiliate_id=' . $this->store->affiliate_code;
    }

    public function validateSlug($slug)
    {
        // Valida formato Shopee (produto-i.SHOP_ID.ITEM_ID)
        if (preg_match('/.*-i\.\d+\.\d+/', $slug)) {
            return true;
        }

        // Valida URLs completas da Shopee
        if (strpos($slug, 'shopee.com') !== false) {
            return true;
        }

        return !empty($slug); // Validação básica para outros formatos
    }
}
