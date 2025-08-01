<?php

namespace hardMOB\Afiliados\Service;

use XF\Service\AbstractService;

class AffiliateGenerator extends AbstractService
{
    public function processText($text, $userId = null)
    {
        // Procura por placeholders no formato {{slug:/produtos/123}}
        $pattern = '/\{\{slug:(.*?)\}\}/';
        
        return preg_replace_callback($pattern, function($matches) use ($userId) {
            return $this->generateAffiliateLink($matches[1], $userId);
        }, $text);
    }

    protected function generateAffiliateLink($slug, $userId = null)
    {
        $cache = $this->app->service('hardMOB\Afiliados:Cache');
        $cacheKey = 'affiliate_link_' . md5($slug);
        
        // Tenta buscar no cache primeiro
        $cachedLink = $cache->get($cacheKey);
        if ($cachedLink) {
            return $this->buildPublicLink($cachedLink['store_id'], $slug);
        }

        // Determina a loja baseada no domínio do slug
        $store = $this->detectStoreFromSlug($slug);
        if (!$store) {
            return $slug; // Retorna o slug original se não conseguir detectar a loja
        }

        // Gera o link de afiliado
        $connector = $store->getConnectorClass();
        if (!$connector->validateSlug($slug)) {
            return $slug;
        }

        $affiliateUrl = $connector->generateAffiliateUrl($slug);
        
        // Armazena no cache
        $cache->set($cacheKey, [
            'store_id' => $store->store_id,
            'affiliate_url' => $affiliateUrl
        ], $this->app->options()->hardmob_afiliados_cache_ttl ?: 3600);

        return $this->buildPublicLink($store->store_id, $slug);
    }

    protected function detectStoreFromSlug($slug)
    {
        $storeRepo = $this->repository('hardMOB\Afiliados:Store');
        
        // Se o slug contém um domínio, tenta detectar pela URL
        if (preg_match('/https?:\/\/([^\/]+)/', $slug, $matches)) {
            $domain = $matches[1];
            return $storeRepo->getStoreByDomain($domain);
        }
        
        // Detecta por padrões conhecidos
        if (strpos($slug, 'amazon.') !== false || preg_match('/^[A-Z0-9]{10}$/', $slug)) {
            return $storeRepo->getStoreByDomain('amazon.com.br');
        }
        
        if (strpos($slug, 'mercadolivre.') !== false || strpos($slug, 'MLB-') === 0) {
            return $storeRepo->getStoreByDomain('mercadolivre.com.br');
        }
        
        if (strpos($slug, 'shopee.') !== false || preg_match('/.*-i\.\d+\.\d+/', $slug)) {
            return $storeRepo->getStoreByDomain('shopee.com.br');
        }

        // Se não conseguir detectar, usa a primeira loja ativa
        return $storeRepo->findActiveStores()->fetchOne();
    }

    protected function buildPublicLink($storeId, $slug)
    {
        return $this->app->router('public')->buildLink('canonical:affiliate', [
            'store_id' => $storeId,
            'slug' => base64_encode($slug)
        ]);
    }

    public function preGenerateLinks()
    {
        $storeRepo = $this->repository('hardMOB\Afiliados:Store');
        $stores = $storeRepo->findActiveStores()->fetch();
        $cache = $this->app->service('hardMOB\Afiliados:Cache');
        
        $generatedCount = 0;
        
        foreach ($stores as $store) {
            $connector = $store->getConnectorClass();
            $testSlugs = $this->getTestSlugs($store);
            
            foreach ($testSlugs as $slug) {
                if ($connector->validateSlug($slug)) {
                    $cacheKey = 'affiliate_link_' . md5($slug);
                    $affiliateUrl = $connector->generateAffiliateUrl($slug);
                    
                    $cache->set($cacheKey, [
                        'store_id' => $store->store_id,
                        'affiliate_url' => $affiliateUrl
                    ], $this->app->options()->hardmob_afiliados_cache_ttl ?: 3600);
                    
                    $generatedCount++;
                }
            }
        }
        
        return $generatedCount;
    }

    protected function getTestSlugs($store)
    {
        // Retorna slugs de teste baseados na loja
        switch (strtolower($store->name)) {
            case 'amazon':
                return ['B08N5WRWNW', 'B07XJ8C8F7', '/dp/B08N5WRWNW'];
                
            case 'mercadolivre':
                return ['MLB-123456789', 'MLB-987654321', '/p/MLB-123456789'];
                
            case 'shopee':
                return ['produto-teste-i.123.456', 'item-exemplo-i.789.012'];
                
            default:
                return ['/produto/teste', '/item/exemplo'];
        }
    }
}
