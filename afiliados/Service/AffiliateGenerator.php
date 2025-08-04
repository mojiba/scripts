<?php

namespace hardMOB\Afiliados\Service;

use XF\Service\AbstractService;
use hardMOB\Afiliados\Helper\Security;
use hardMOB\Afiliados\Entity\AuditLog;

class AffiliateGenerator extends AbstractService
{
    protected $cache;
    protected $config;

    public function __construct(\XF\App $app)
    {
        parent::__construct($app);
        // Defer service loading to avoid circular dependencies during app setup
    }
    
    protected function getCache()
    {
        if (!$this->cache) {
            $this->cache = $this->app->service('hardMOB\Afiliados:Cache');
        }
        return $this->cache;
    }
    
    protected function getConfig()
    {
        if (!$this->config) {
            $this->config = $this->app->service('hardMOB\Afiliados:Configuration');
        }
        return $this->config;
    }

    public function processText($text, $userId = null)
    {
        // Simplified version without rate limiting to avoid startup crashes
        // Procura por placeholders no formato {{slug:/produtos/123}}
        $pattern = '/\{\{slug:(.*?)\}\}/';
        
        return preg_replace_callback($pattern, function($matches) use ($userId) {
            return $this->generateAffiliateLink($matches[1], $userId);
        }, $text);
    }

    protected function generateAffiliateLink($slug, $userId = null)
    {
        try {
            // Basic security validation - simplified to avoid crashes
            if (empty($slug) || strlen($slug) > 500) {
                return $slug;
            }

            $cacheKey = 'affiliate_link_' . md5($slug);
            
            // Try to get from cache first
            try {
                $cachedLink = $this->getCache()->get($cacheKey);
                if ($cachedLink) {
                    return $this->buildPublicLink($cachedLink['store_id'], $slug);
                }
            } catch (\Exception $e) {
                // Cache failure - continue without cache
            }

            // Detect store from slug
            $store = $this->detectStoreFromSlug($slug);
            if (!$store) {
                return $slug; // Return original if can't detect store
            }

            // Generate affiliate URL
            $connector = $store->getConnectorClass();
            if (!$connector || !$connector->validateSlug($slug)) {
                return $slug;
            }

            $affiliateUrl = $connector->generateAffiliateUrl($slug);
            
            // Cache the result (but don't fail if cache fails)
            try {
                $cacheTtl = $this->app->options()->hardmob_afiliados_cache_ttl ?: 3600;
                $this->getCache()->set($cacheKey, [
                    'store_id' => $store->store_id,
                    'affiliate_url' => $affiliateUrl
                ], $cacheTtl);
            } catch (\Exception $e) {
                // Cache failure - continue without cache
            }

            return $this->buildPublicLink($store->store_id, $slug);
            
        } catch (\Exception $e) {
            // Any error - return original slug to avoid breaking page
            return $slug;
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
        
        // Detecta por padrões conhecidos com validação
        if (strpos($slug, 'amazon.') !== false || preg_match('/^[A-Z0-9]{10}$/', $slug)) {
            return $storeRepo->getStoreByDomain('amazon.com.br');
        }
        
        if (strpos($slug, 'mercadolivre.') !== false || strpos($slug, 'MLB-') === 0) {
            return $storeRepo->getStoreByDomain('mercadolivre.com.br');
        }
        
        if (strpos($slug, 'shopee.') !== false || preg_match('/.*-i\.\d+\.\d+/', $slug)) {
            return $storeRepo->getStoreByDomain('shopee.com.br');
        }

        // Se não conseguir detectar, retorna null (mudança de comportamento para maior segurança)
        return null;
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
        // Check if pre-generation is enabled
        if (!$this->config->get('enable_pregeneration', false)) {
            return 0;
        }

        $storeRepo = $this->repository('hardMOB\Afiliados:Store');
        $stores = $storeRepo->findActiveStores()->fetch();
        
        $generatedCount = 0;
        $maxLinks = $this->config->get('max_links_per_page', 50);
        
        foreach ($stores as $store) {
            if ($generatedCount >= $maxLinks) {
                break;
            }
            
            try {
                $connector = $store->getConnectorClass();
                $testSlugs = $this->getTestSlugs($store);
                
                foreach ($testSlugs as $slug) {
                    if ($generatedCount >= $maxLinks) {
                        break;
                    }
                    
                    if ($connector->validateSlug($slug)) {
                        $cacheKey = 'affiliate_link_' . md5($slug);
                        $affiliateUrl = $connector->generateAffiliateUrl($slug);
                        
                        // Validate generated URL
                        if (Security::validateUrl($affiliateUrl)) {
                            $cacheTtl = $this->getConfig()->get('cache_ttl', 3600);
                            $this->getCache()->set($cacheKey, [
                                'store_id' => $store->store_id,
                                'affiliate_url' => $affiliateUrl
                            ], $cacheTtl);
                            
                            $generatedCount++;
                        }
                    }
                }
            } catch (\Exception $e) {
                // Log error but continue with other stores
                Security::logSecurityEvent('pregeneration_error', [
                    'store_id' => $store->store_id,
                    'error' => $e->getMessage()
                ], 'error');
            }
        }
        
        // Log pre-generation activity
        if ($generatedCount > 0) {
            AuditLog::logEvent(
                AuditLog::EVENT_CREATE,
                'PreGeneration',
                0,
                "Pre-generated {$generatedCount} affiliate links"
            );
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

    /**
     * Clean expired cache entries
     */
    public function cleanExpiredCache()
    {
        return $this->getCache()->cleanExpired();
    }

    /**
     * Get generation statistics
     */
    public function getGenerationStats()
    {
        return $this->getCache()->getStats();
    }
}
