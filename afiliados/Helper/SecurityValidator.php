<?php

namespace hardMOB\Afiliados\Helper;

use XF\Util\Php;

/**
 * Security validation and sanitization helper for affiliate link processing
 * Implements XSS and injection prevention using XenForo and PHP native functions
 */
class SecurityValidator
{
    protected $app;
    
    // Maximum allowed lengths to prevent DoS attacks
    const MAX_TEXT_LENGTH = 50000; // 50KB
    const MAX_SLUG_LENGTH = 2048;  // 2KB
    const MAX_URL_LENGTH = 2048;   // 2KB
    
    // Safe regex patterns to prevent ReDoS
    const PLACEHOLDER_PATTERN = '/\{\{slug:((?:[^{}]|\\{[^{]|\\}[^}]){1,500})\}\}/';
    const URL_DOMAIN_PATTERN = '/^https?:\/\/([a-zA-Z0-9.-]{1,253})/';
    const AMAZON_ASIN_PATTERN = '/^[A-Z0-9]{10}$/';
    const MLB_PATTERN = '/^MLB-\d{9,12}$/';
    const SHOPEE_PATTERN = '/^[a-zA-Z0-9._-]+-i\.\d{1,10}\.\d{1,10}$/';

    public function __construct(\XF\App $app)
    {
        $this->app = $app;
    }

    /**
     * Validate and sanitize text input for processText method
     */
    public function validateText($text)
    {
        if (!is_string($text)) {
            return '';
        }

        // Length check to prevent DoS
        if (strlen($text) > self::MAX_TEXT_LENGTH) {
            throw new \InvalidArgumentException('Text input too long');
        }

        // Basic XSS prevention - strip dangerous tags but allow safe formatting
        $text = strip_tags($text, '<b><i><u><em><strong><p><br>');
        
        // Encode HTML entities to prevent XSS
        $text = htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        return $text;
    }

    /**
     * Validate and sanitize slug input
     */
    public function validateSlug($slug)
    {
        if (!is_string($slug)) {
            return '';
        }

        // Length check
        if (strlen($slug) > self::MAX_SLUG_LENGTH) {
            throw new \InvalidArgumentException('Slug too long');
        }

        // Remove null bytes and control characters
        $slug = preg_replace('/[\x00-\x1F\x7F]/', '', $slug);
        
        // If it looks like a URL, validate it properly
        if (preg_match('/^https?:\/\//', $slug)) {
            return $this->validateUrl($slug);
        }

        // For non-URL slugs, basic sanitization
        $slug = trim($slug);
        
        return $slug;
    }

    /**
     * Validate URL using filter_var and additional checks
     */
    public function validateUrl($url)
    {
        if (!is_string($url)) {
            return '';
        }

        // Length check
        if (strlen($url) > self::MAX_URL_LENGTH) {
            throw new \InvalidArgumentException('URL too long');
        }

        // Basic URL validation
        $cleanUrl = filter_var($url, FILTER_VALIDATE_URL);
        if (!$cleanUrl) {
            return '';
        }

        $parsed = parse_url($cleanUrl);
        if (!$parsed || !isset($parsed['scheme']) || !isset($parsed['host'])) {
            return '';
        }

        // Only allow HTTP/HTTPS
        if (!in_array($parsed['scheme'], ['http', 'https'])) {
            return '';
        }

        // Validate hostname
        if (!filter_var($parsed['host'], FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            return '';
        }

        return $cleanUrl;
    }

    /**
     * Safe regex matching to prevent ReDoS attacks
     */
    public function safeRegexMatch($pattern, $subject, $limit = 1000)
    {
        if (!is_string($subject) || strlen($subject) > $limit) {
            return false;
        }

        // Set PCRE limits to prevent ReDoS
        $oldBacktrackLimit = ini_get('pcre.backtrack_limit');
        $oldRecursionLimit = ini_get('pcre.recursion_limit');
        
        ini_set('pcre.backtrack_limit', '10000');
        ini_set('pcre.recursion_limit', '1000');

        $result = preg_match($pattern, $subject, $matches);

        // Restore original limits
        ini_set('pcre.backtrack_limit', $oldBacktrackLimit);
        ini_set('pcre.recursion_limit', $oldRecursionLimit);

        return $result ? $matches : false;
    }

    /**
     * Extract domain safely from URL
     */
    public function extractDomain($url)
    {
        $cleanUrl = $this->validateUrl($url);
        if (!$cleanUrl) {
            return '';
        }

        $matches = $this->safeRegexMatch(self::URL_DOMAIN_PATTERN, $cleanUrl);
        return $matches ? $matches[1] : '';
    }

    /**
     * Validate Amazon ASIN
     */
    public function validateAmazonASIN($asin)
    {
        if (!is_string($asin)) {
            return false;
        }
        
        return (bool) $this->safeRegexMatch(self::AMAZON_ASIN_PATTERN, $asin);
    }

    /**
     * Validate MercadoLivre product ID
     */
    public function validateMLBId($mlbId)
    {
        if (!is_string($mlbId)) {
            return false;
        }
        
        return (bool) $this->safeRegexMatch(self::MLB_PATTERN, $mlbId);
    }

    /**
     * Validate Shopee product slug
     */
    public function validateShopeeSlug($slug)
    {
        if (!is_string($slug)) {
            return false;
        }
        
        return (bool) $this->safeRegexMatch(self::SHOPEE_PATTERN, $slug);
    }

    /**
     * Validate base64 encoded data
     */
    public function validateBase64($data, $maxLength = 1024)
    {
        if (!is_string($data) || strlen($data) > $maxLength) {
            return '';
        }

        // Check if it's valid base64
        if (!preg_match('/^[A-Za-z0-9+\/]*={0,2}$/', $data)) {
            return '';
        }

        $decoded = base64_decode($data, true);
        if ($decoded === false) {
            return '';
        }

        // Additional validation on decoded content
        $decoded = $this->validateSlug($decoded);
        
        return $decoded;
    }

    /**
     * Sanitize output for templates to prevent XSS
     */
    public function sanitizeOutput($output)
    {
        if (!is_string($output)) {
            return '';
        }

        // Use XenForo's escaping if available
        if (method_exists($this->app, 'stringFormatter')) {
            return $this->app->stringFormatter()->censorText($output);
        }

        // Fallback to basic HTML encoding
        return htmlspecialchars($output, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Generate safe cache key
     */
    public function generateSafeCacheKey($prefix, $data)
    {
        // Ensure prefix is safe
        $prefix = preg_replace('/[^a-zA-Z0-9_-]/', '', $prefix);
        
        // Hash the data to ensure consistent length and safety
        $hash = hash('sha256', serialize($data));
        
        return $prefix . '_' . $hash;
    }
}