<?php

namespace hardMOB\AfiliateLinks\Handler;

use XF\Service\AbstractService;

class MercadoLivreHandler extends AbstractService
{
    /**
     * Processa URL do MercadoLivre
     * @version 1.0.0 (2025-07-21)
     */
    public function processUrl(string $url, string $param): string
    {
        $parts = parse_url($url);
        if (empty($parts['path'])) {
            return $url;
        }
        
        // Limpa qualquer query string existente
        $base = $parts['scheme'] . '://' . $parts['host'] . $parts['path'];
        return $base . '?' . $param;
    }
}