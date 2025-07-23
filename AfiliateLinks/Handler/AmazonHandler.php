<?php

namespace hardMOB\AfiliateLinks\Handler;

use XF\Service\AbstractService;

class AmazonHandler extends AbstractService
{
    /**
     * Processa URL da Amazon
     */
    public function processUrl(string $url, string $param): string
    {
        // Extrair ASIN do link
        $regex = '#(?:dp|gp/(?:product|aw/d))/([A-Z0-9]{10})#i';
        if (preg_match($regex, $url, $matches)) {
            $asin = $matches[1];
            
            // Remove qualquer parâmetro de afiliado já presente
            $urlSanitized = preg_replace('#(\?|&)tag=[^&"]*#i', '', $url);
            
            // Extrai smid, se existir
            if (preg_match('#smid=([A-Z0-9]+)#i', $urlSanitized, $smidMatch)) {
                $smid = $smidMatch[1];
            } else {
                $smid = '';
            }
            
            // Monta a URL base
            $newUrl = "https://www.amazon.com.br/gp/product/{$asin}";
            $qs = [];
            if ($smid) {
                $qs[] = "smid={$smid}";
            }
            
            $qs[] = $param; // param já vem como "tag=SEUAFILIADO"
            $newUrl .= '?' . implode('&', $qs);
            
            return $newUrl;
        }
        
        // Promoções
        if (preg_match('#/promotion/psp/([A-Z0-9]+)#i', $url)) {
            $base = preg_replace('/[?#].*$/', '', $url);
            return $base . '?' . $param;
        }
        
        // Página de loja/marca
        if (preg_match('#/stores/[^/]+/page/[^/]+#i', $url)) {
            $base = preg_replace('/[?#].*$/', '', $url);
            return $base . '?' . $param;
        }
        
        // Ofertas, busca, categorias
        if (preg_match('#/(gp/goldbox|s|b|brand/)#i', $url)) {
            $sep = (strpos($url, '?') === false) ? '?' : '&';
            return $url . $sep . $param;
        }
        
        // Link genérico
        $clean = preg_replace('/[#].*$/', '', $url);
        $sep = (strpos($clean, '?') === false) ? '?' : '&';
        return $clean . $sep . $param;
    }
}