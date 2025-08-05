<?php

namespace hardMOB\Afiliados\Connector;

use hardMOB\Afiliados\Helper\Security;

/**
 * Default connector for custom stores
 */
class CustomStore implements StoreInterface
{
    protected $store;

    public function __construct(\hardMOB\Afiliados\Entity\Store $store)
    {
        $this->store = $store;
    }

    public function generateAffiliateUrl($slug)
    {
        // Validate slug first
        if (!Security::validateSlug($slug)) {
            throw new \InvalidArgumentException('Invalid slug provided');
        }
        
        // Sanitize the slug
        $slug = Security::sanitizeInput($slug, 'url');
        
        // Default implementation - customize per store
        $baseUrl = 'https://' . $this->store->domain;
        
        // If slug already contains the domain, extract the path
        if (strpos($slug, '://') !== false) {
            $parsed = parse_url($slug);
            if ($parsed && isset($parsed['path'])) {
                $slug = $parsed['path'];
                if (isset($parsed['query'])) {
                    $slug .= '?' . $parsed['query'];
                }
            }
        }
        
        // Ensure slug starts with /
        if (!str_starts_with($slug, '/')) {
            $slug = '/' . $slug;
        }
        
        // Check if affiliate parameter already exists
        if (strpos($slug, 'ref=') !== false || 
            strpos($slug, 'affiliate') !== false ||
            strpos($slug, 'partner') !== false ||
            strpos($slug, 'tag=') !== false) {
            return $baseUrl . $slug;
        }
        
        // Add affiliate code as parameter
        $separator = strpos($slug, '?') !== false ? '&' : '?';
        $affiliateParam = $this->getAffiliateParam();
        
        return $baseUrl . $slug . $separator . $affiliateParam . '=' . $this->store->affiliate_code;
    }

    public function validateSlug($slug)
    {
        // Basic validation using Security helper
        if (!Security::validateSlug($slug)) {
            return false;
        }
        
        // Store-specific validation can be added here
        return !empty($slug) && (
            filter_var($slug, FILTER_VALIDATE_URL) !== false ||
            strpos($slug, '/') !== false ||
            strlen($slug) > 3
        );
    }

    /**
     * Get the affiliate parameter name for this store
     */
    protected function getAffiliateParam()
    {
        // Common affiliate parameter names by store
        $domain = strtolower($this->store->domain);
        
        if (strpos($domain, 'amazon') !== false) {
            return 'tag';
        } elseif (strpos($domain, 'mercadolivre') !== false || strpos($domain, 'mercadolibre') !== false) {
            return 'pdp_machine';
        } elseif (strpos($domain, 'shopee') !== false) {
            return 'af_sitename';
        } else {
            // Default parameter name
            return 'ref';
        }
    }
}
