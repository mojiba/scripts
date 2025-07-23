<?php

namespace hardMOB\AfiliateLinks\Handler;

use XF\Service\AbstractService;

class MagazineVoceHandler extends AbstractService
{
    /**
     * Processa URL do Magazine Você
     * @version 1.0.0 (2025-07-21)
     */
    public function processUrl(string $url, string $param): string
    {
        $parts = parse_url($url);
        if (empty($parts['path'])) {
            return $url;
        }
        
        $pathParts = explode('/', ltrim($parts['path'], '/'));
        if (!empty($pathParts[0])) {
            array_shift($pathParts);  // descarta o primeiro "segmento" original
        }
        
        // https://www.magazinevoce.com.br/{param}/{resto-do-path}
        return $parts['scheme'] . '://' . $parts['host'] 
             . '/' . $param . '/' . implode('/', $pathParts);
    }
}