<?php

namespace hardMOB\AfiliateLinks\Handler;

use XF\Service\AbstractService;

class ShopeeHandler extends AbstractService
{
    /**
     * Processa URL da Shopee
     * @version 1.0.0 (2025-07-21)
     */
    public function processUrl(string $url, string $param): string
    {
        $parts = parse_url($url);
        if (empty($parts['path'])) {
            return $url;
        }
        
        $base = $parts['scheme'] . '://' . $parts['host'] . $parts['path'];
        return $base . '?' . $param;
    }
}