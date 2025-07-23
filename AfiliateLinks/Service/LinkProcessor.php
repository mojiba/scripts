<?php

namespace hardMOB\AfiliateLinks\Service;

use XF\Service\AbstractService;

class LinkProcessor extends AbstractService
{
    /**
     * Processa uma URL e retorna a versão com afiliado, se aplicável
     */
    public function processUrl(string $url): string
    {
        // Verificar se a URL já tem a classe de afiliado no HTML
        if (strpos($url, 'link-afiliado') !== false) {
            return $url;
        }

        $parts = parse_url($url);
        if (empty($parts['host'])) {
            return $url;
        }

        $host = preg_replace('#^www\.#i', '', strtolower($parts['host']));
        
        // Tratar domínios específicos
        if (preg_match('#(?:^|\.)mercadolivre\.com\.br$#i', $host)) {
            $host = 'mercadolivre.com.br';
        }
        
        // Tratar domínios do AliExpress
        if (preg_match('#(?:^|\.)aliexpress\.com$#i', $host)) {
            $host = 'aliexpress.com';
            
            // Use handler específico para AliExpress, independente do mapa de lojas
            $handler = $this->app()->service('hardMOB\AfiliateLinks:Handler\AliExpressHandler');
            return $handler->processUrl($url, '');
        }
        
        /** @var \hardMOB\AfiliateLinks\Store\AffiliateStores $storeManager */
        $storeManager = $this->app()->service('hardMOB\AfiliateLinks:Store\AffiliateStores');
        
        $map = $storeManager->getStoresMap();
        if (empty($map) || !isset($map[$host])) {
            return $url;
        }
        
        // Obter handler apropriado para esta loja
        $handler = $this->getHandler($host);
        if ($handler) {
            return $handler->processUrl($url, $map[$host]);
        }
        
        // Handler genérico para lojas sem tratamento especial
        return $this->getGenericHandler()->processUrl($url, $map[$host]);
    }
    
    /**
     * Retorna o handler para o domínio específico
     */
    protected function getHandler(string $domain)
    {
        $handlerMap = [
            'amazon.com.br' => 'hardMOB\AfiliateLinks:Handler\AmazonHandler',
            'shopee.com.br' => 'hardMOB\AfiliateLinks:Handler\ShopeeHandler',
            'terabyteshop.com.br' => 'hardMOB\AfiliateLinks:Handler\TerabyteHandler',
            'mercadolivre.com.br' => 'hardMOB\AfiliateLinks:Handler\MercadoLivreHandler',
            'magazineluiza.com.br' => 'hardMOB\AfiliateLinks:Handler\MagaluHandler',
            'magazinevoce.com.br' => 'hardMOB\AfiliateLinks:Handler\MagazineVoceHandler',
            'aliexpress.com' => 'hardMOB\AfiliateLinks:Handler\AliExpressHandler'
        ];
        
        if (isset($handlerMap[$domain])) {
            return $this->app()->service($handlerMap[$domain]);
        }
        
        return null;
    }
    
    /**
     * Retorna o handler genérico
     */
    protected function getGenericHandler()
    {
        return $this->app()->service('hardMOB\AfiliateLinks:Handler\GenericHandler');
    }
}