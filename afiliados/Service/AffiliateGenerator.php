<?php

namespace hardMOB\Afiliados\Service;

use XF\Service\AbstractService;

class AffiliateGenerator extends AbstractService
{
    protected $errorHandler;

    public function __construct(\XF\App $app)
    {
        parent::__construct($app);
        $this->errorHandler = $app->service('hardMOB\Afiliados:ErrorHandler');
    }
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
        try {
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
                $this->errorHandler->logError('store_not_found', 'No store found for slug', ['slug' => $slug]);
                return $slug; // Retorna o slug original se não conseguir detectar a loja
            }

            // Verifica se a loja tem código de afiliado
            if (empty($store->affiliate_code)) {
                $this->errorHandler->logError('affiliate_code_missing', 'Affiliate code missing for store', ['store' => $store->name]);
                return $slug;
            }

            // Gera o link de afiliado
            $connector = $store->getConnectorClass();
            if (!$connector) {
                $this->errorHandler->logError('connector_missing', 'Store connector not found', ['store' => $store->name]);
                return $slug;
            }

            if (!$connector->validateSlug($slug)) {
                $this->errorHandler->logError('invalid_slug', 'Invalid slug format', ['slug' => $slug]);
                return $slug;
            }

            $affiliateUrl = $connector->generateAffiliateUrl($slug);
            
            // Armazena no cache
            $cache->set($cacheKey, [
                'store_id' => $store->store_id,
                'affiliate_url' => $affiliateUrl
            ], $this->app->options()->hardmob_afiliados_cache_ttl ?: 3600);

            return $this->buildPublicLink($store->store_id, $slug);
            
        } catch (\Exception $e) {
            $this->errorHandler->logError('link_generation_failed', 'Link generation failed', ['slug' => $slug, 'error' => $e->getMessage()], $e);
            return $slug; // Fallback to original slug
        }
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
        try {
            $storeRepo = $this->repository('hardMOB\Afiliados:Store');
            $stores = $storeRepo->findActiveStores()->fetch();
            
            if ($stores->count() === 0) {
                $this->errorHandler->logWarning('cache_unavailable', 'No active stores found for pre-generation');
                return 0;
            }

            $cache = $this->app->service('hardMOB\Afiliados:Cache');
            $generatedCount = 0;
            
            foreach ($stores as $store) {
                try {
                    $connector = $store->getConnectorClass();
                    if (!$connector) {
                        $this->errorHandler->logError('connector_missing', 'Store connector not found', ['store' => $store->name]);
                        continue;
                    }

                    $testSlugs = $this->getTestSlugs($store);
                    
                    foreach ($testSlugs as $slug) {
                        try {
                            if ($connector->validateSlug($slug)) {
                                $cacheKey = 'affiliate_link_' . md5($slug);
                                $affiliateUrl = $connector->generateAffiliateUrl($slug);
                                
                                $cache->set($cacheKey, [
                                    'store_id' => $store->store_id,
                                    'affiliate_url' => $affiliateUrl
                                ], $this->app->options()->hardmob_afiliados_cache_ttl ?: 3600);
                                
                                $generatedCount++;
                            }
                        } catch (\Exception $e) {
                            $this->errorHandler->logError('link_generation_failed', 'Failed to pre-generate link', ['slug' => $slug, 'store' => $store->name, 'error' => $e->getMessage()], $e);
                        }
                    }
                } catch (\Exception $e) {
                    $this->errorHandler->logError('link_generation_failed', 'Failed to process store for pre-generation', ['store' => $store->name, 'error' => $e->getMessage()], $e);
                }
            }
            
            return $generatedCount;
            
        } catch (\Exception $e) {
            $this->errorHandler->logError('link_generation_failed', 'Pre-generation process failed', ['error' => $e->getMessage()], $e);
            return 0;
        }
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
