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
        $this->cache = $app->service('hardMOB\Afiliados:Cache');
        $this->config = $app->service('hardMOB\Afiliados:Configuration');
    }

    public function processText($text, $userId = null)
    {
        // Rate limiting check
        $rateLimitKey = 'affiliate_generation_' . ($userId ?: $this->app->request()->getIp());
        if (!Security::checkRateLimit($rateLimitKey, 50, 3600)) {
            Security::logSecurityEvent('affiliate_rate_limit_exceeded', [
                'user_id' => $userId,
                'ip' => $this->app->request()->getIp()
            ], 'warning');
            
            throw new \Exception('Rate limit exceeded for affiliate link generation');
        }

        // Procura por placeholders no formato {{slug:/produtos/123}}
        $pattern = '/\{\{slug:(.*?)\}\}/';
        
        return preg_replace_callback($pattern, function($matches) use ($userId) {
            return $this->generateAffiliateLink($matches[1], $userId);
        }, $text);
    }

    protected function generateAffiliateLink($slug, $userId = null)
    {
        // Security validation
        if (!Security::validateSlug($slug)) {
            Security::logSecurityEvent('invalid_slug_attempt', [
                'slug' => $slug,
                'user_id' => $userId
            ], 'warning');
            
            return $slug; // Return original if invalid
        }

        $cacheKey = 'affiliate_link_' . md5($slug);
        
        // Tenta buscar no cache primeiro
        $cachedLink = $this->cache->get($cacheKey);
        if ($cachedLink) {
            return $this->buildPublicLink($cachedLink['store_id'], $slug);
        }

        // Determina a loja baseada no domínio do slug
        $store = $this->detectStoreFromSlug($slug);
        if (!$store) {
            // Log failed detection
            AuditLog::logEvent(
                AuditLog::EVENT_SECURITY,
                'LinkGeneration',
                0,
                'Failed to detect store for slug: ' . substr($slug, 0, 100),
                ['slug' => $slug, 'user_id' => $userId]
            );
            
            return $slug; // Retorna o slug original se não conseguir detectar a loja
        }

        // Validate domain is allowed
        if (!$store->isDomainAllowed()) {
            Security::logSecurityEvent('domain_not_allowed', [
                'domain' => $store->domain,
                'slug' => $slug,
                'user_id' => $userId
            ], 'warning');
            
            return $slug;
        }

        try {
            // Gera o link de afiliado
            $connector = $store->getConnectorClass();
            if (!$connector->validateSlug($slug)) {
                return $slug;
            }

            $affiliateUrl = $connector->generateAffiliateUrl($slug);
            
            // Validate generated URL
            if (!Security::validateUrl($affiliateUrl)) {
                Security::logSecurityEvent('invalid_generated_url', [
                    'url' => $affiliateUrl,
                    'store_id' => $store->store_id,
                    'slug' => $slug
                ], 'error');
                
                return $slug;
            }
            
            // Armazena no cache
            $cacheTtl = $this->config->get('cache_ttl', 3600);
            $this->cache->set($cacheKey, [
                'store_id' => $store->store_id,
                'affiliate_url' => $affiliateUrl
            ], $cacheTtl);

            // Log successful generation
            AuditLog::logEvent(
                AuditLog::EVENT_CREATE,
                'AffiliateLink',
                $store->store_id,
                'Affiliate link generated',
                [],
                ['slug' => $slug, 'store' => $store->name, 'user_id' => $userId]
            );

            return $this->buildPublicLink($store->store_id, $slug);
            
        } catch (\Exception $e) {
            // Log error
            Security::logSecurityEvent('affiliate_generation_error', [
                'error' => $e->getMessage(),
                'slug' => $slug,
                'store_id' => $store->store_id
            ], 'error');
            
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
                            $cacheTtl = $this->config->get('cache_ttl', 3600);
                            $this->cache->set($cacheKey, [
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
        return $this->cache->cleanExpired();
    }

    /**
     * Get generation statistics
     */
    public function getGenerationStats()
    {
        return $this->cache->getStats();
    }
}
