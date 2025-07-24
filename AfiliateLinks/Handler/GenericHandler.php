<?php

namespace hardMOB\AfiliateLinks\Handler;

use XF\Service\AbstractService;

class GenericHandler extends AbstractService
{
    /**
     * Processa URL de lojas genéricas
     * @version 1.0.0 (2025-07-21)
     */
    public function processUrl(string $url, string $param): string
    {
        $clean = preg_replace('/#.*/', '', $url);
        $sep = (strpos($clean, '?') === false) ? '?' : '&';
        return $clean . $sep . $param;
    }
}