<?php

namespace hardMOB\Afiliados\Service;

use XF\Service\AbstractService;
use hardMOB\Afiliados\Helper\SecurityValidator;

class AffiliateGenerator extends AbstractService
{
    protected $securityValidator;

    public function __construct(\XF\App $app, $request = null)
    {
        parent::__construct($app, $request);
        $this->securityValidator = new SecurityValidator($app);
    }

    public function processText($text, $userId = null)
    {
        try {
            // Validate and sanitize input text
            $sanitizedText = $this->securityValidator->validateText($text);
            
            // Use safe regex pattern to prevent ReDoS attacks
            $pattern = SecurityValidator::PLACEHOLDER_PATTERN;
            
            return preg_replace_callback($pattern, function($matches) use ($userId) {
                // Validate the slug from the match
                $slug = $this->securityValidator->validateSlug($matches[1]);
                if (empty($slug)) {
                    return $matches[0]; // Return original if validation fails
                }
                return $this->generateAffiliateLink($slug, $userId);
            }, $sanitizedText);
        } catch (\InvalidArgumentException $e) {
            // Log security violation attempt
            \XF::logError('Affiliate link processing security violation: ' . $e->getMessage());
            return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
    }

    protected function generateAffiliateLink($slug, $userId = null)
    {
        try {
            // Validate and sanitize the slug
            $sanitizedSlug = $this->securityValidator->validateSlug($slug);
            if (empty($sanitizedSlug)) {
                return htmlspecialchars($slug, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }

            $cache = $this->app->service('hardMOB\Afiliados:Cache');
            
            // Generate safe cache key
            $cacheKey = $this->securityValidator->generateSafeCacheKey('affiliate_link', $sanitizedSlug);
            
            // Tenta buscar no cache primeiro
            $cachedLink = $cache->get($cacheKey);
            if ($cachedLink) {
                return $this->buildPublicLink($cachedLink['store_id'], $sanitizedSlug);
            }

            // Determina a loja baseada no domínio do slug
            $store = $this->detectStoreFromSlug($sanitizedSlug);
            if (!$store) {
                return htmlspecialchars($sanitizedSlug, ENT_QUOTES | ENT_HTML5, 'UTF-8'); // Return sanitized original if no store detected
            }

            // Gera o link de afiliado
            $connector = $store->getConnectorClass();
            if (!$connector->validateSlug($sanitizedSlug)) {
                return htmlspecialchars($sanitizedSlug, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }

            $affiliateUrl = $connector->generateAffiliateUrl($sanitizedSlug);
            
            // Armazena no cache
            $cache->set($cacheKey, [
                'store_id' => $store->store_id,
                'affiliate_url' => $affiliateUrl
            ], $this->app->options()->hardmob_afiliados_cache_ttl ?: 3600);

            return $this->buildPublicLink($store->store_id, $sanitizedSlug);
        } catch (\Exception $e) {
            // Log error and return sanitized input
            \XF::logError('Affiliate link generation error: ' . $e->getMessage());
            return htmlspecialchars($slug, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
    }

    protected function detectStoreFromSlug($slug)
    {
        $storeRepo = $this->repository('hardMOB\Afiliados:Store');
        
        // Se o slug contém um domínio, tenta detectar pela URL usando validação segura
        $domain = $this->securityValidator->extractDomain($slug);
        if (!empty($domain)) {
            return $storeRepo->getStoreByDomain($domain);
        }
        
        // Detecta por padrões conhecidos usando validação segura
        if (strpos($slug, 'amazon.') !== false || $this->securityValidator->validateAmazonASIN($slug)) {
            return $storeRepo->getStoreByDomain('amazon.com.br');
        }
        
        if (strpos($slug, 'mercadolivre.') !== false || $this->securityValidator->validateMLBId($slug)) {
            return $storeRepo->getStoreByDomain('mercadolivre.com.br');
        }
        
        if (strpos($slug, 'shopee.') !== false || $this->securityValidator->validateShopeeSlug($slug)) {
            return $storeRepo->getStoreByDomain('shopee.com.br');
        }

        // Se não conseguir detectar, usa a primeira loja ativa
        return $storeRepo->findActiveStores()->fetchOne();
    }

    protected function buildPublicLink($storeId, $slug)
    {
        // Sanitize slug before encoding
        $sanitizedSlug = $this->securityValidator->validateSlug($slug);
        if (empty($sanitizedSlug)) {
            return '';
        }

        return $this->app->router('public')->buildLink('canonical:affiliate', [
            'store_id' => (int) $storeId,
            'slug' => base64_encode($sanitizedSlug)
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
