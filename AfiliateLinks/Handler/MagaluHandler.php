<?php

namespace hardMOB\AfiliateLinks\Handler;

use XF\Service\AbstractService;

class MagaluHandler extends AbstractService
{
    /**
     * Converte links da Magazine Luiza para Magazine Você
     * @version 1.0.0 (2025-07-21)
     */
    public function processUrl(string $url, string $param): string
    {
        $parts = parse_url($url);
        if (empty($parts['path'])) {
            return $url;
        }
        
        return 'https://www.magazinevoce.com.br/' . $param . $parts['path'];
    }
}