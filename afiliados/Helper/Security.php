<?php

namespace hardMOB\Afiliados\Helper;

use XF\Util\Url;

class Security
{
    /**
     * Validates and sanitizes URLs
     */
    public static function validateUrl($url, array $allowedDomains = [])
    {
        if (empty($url)) {
            return false;
        }

        // Remove any potential XSS vectors
        $url = filter_var($url, FILTER_SANITIZE_URL);
        
        // Validate URL format
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        // Parse URL to check domain
        $parsedUrl = parse_url($url);
        if (!$parsedUrl || empty($parsedUrl['host'])) {
            return false;
        }

        // Check against allowed domains if specified
        if (!empty($allowedDomains)) {
            $domain = strtolower($parsedUrl['host']);
            $allowed = false;
            
            foreach ($allowedDomains as $allowedDomain) {
                $allowedDomain = strtolower($allowedDomain);
                if ($domain === $allowedDomain || 
                    str_ends_with($domain, '.' . $allowedDomain)) {
                    $allowed = true;
                    break;
                }
            }
            
            if (!$allowed) {
                return false;
            }
        }

        // Block dangerous protocols
        $scheme = strtolower($parsedUrl['scheme'] ?? '');
        if (!in_array($scheme, ['http', 'https'])) {
            return false;
        }

        return true;
    }

    /**
     * Sanitizes input data
     */
    public static function sanitizeInput($input, $type = 'string')
    {
        if (is_array($input)) {
            return array_map(function($item) use ($type) {
                return self::sanitizeInput($item, $type);
            }, $input);
        }

        switch ($type) {
            case 'string':
                return trim(strip_tags($input));
            
            case 'html':
                return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            
            case 'url':
                return filter_var($input, FILTER_SANITIZE_URL);
            
            case 'email':
                return filter_var($input, FILTER_SANITIZE_EMAIL);
            
            case 'int':
                return (int) $input;
            
            case 'float':
                return (float) $input;
            
            case 'domain':
                $domain = strtolower(trim($input));
                $domain = preg_replace('#^https?://#i', '', $domain);
                $domain = rtrim($domain, '/');
                return preg_replace('/[^a-z0-9\-\.]/', '', $domain);
            
            default:
                return trim($input);
        }
    }

    /**
     * Validates domain name
     */
    public static function validateDomain($domain)
    {
        $domain = self::sanitizeInput($domain, 'domain');
        
        if (empty($domain)) {
            return false;
        }

        // Check domain format
        if (!filter_var('http://' . $domain, FILTER_VALIDATE_URL)) {
            return false;
        }

        // Check for minimum domain requirements
        if (substr_count($domain, '.') < 1) {
            return false;
        }

        // Block localhost and private IPs
        if (in_array($domain, ['localhost', '127.0.0.1', '::1']) ||
            preg_match('/^(10\.|192\.168\.|172\.(1[6-9]|2[0-9]|3[01])\.)/', $domain)) {
            return false;
        }

        return true;
    }

    /**
     * Validates affiliate code format
     */
    public static function validateAffiliateCode($code)
    {
        $code = self::sanitizeInput($code);
        
        if (empty($code)) {
            return false;
        }

        // Basic format validation - alphanumeric with some special chars
        if (!preg_match('/^[a-zA-Z0-9\-_]{3,50}$/', $code)) {
            return false;
        }

        return true;
    }

    /**
     * Rate limiting check
     */
    public static function checkRateLimit($key, $maxAttempts = 10, $timeWindow = 3600)
    {
        $cache = \XF::app()->cache();
        if (!$cache) {
            return true; // Allow if no cache available
        }

        $cacheKey = 'rate_limit_' . md5($key);
        $attempts = $cache->fetch($cacheKey) ?: 0;

        if ($attempts >= $maxAttempts) {
            return false;
        }

        $cache->save($cacheKey, $attempts + 1, $timeWindow);
        return true;
    }

    /**
     * Validates CSRF token
     */
    public static function validateCsrfToken($token)
    {
        $visitor = \XF::visitor();
        return $visitor->csrf_token && hash_equals($visitor->csrf_token, $token);
    }

    /**
     * Logs security events
     */
    public static function logSecurityEvent($event, $details = [], $severity = 'info')
    {
        $logData = [
            'event' => $event,
            'details' => $details,
            'severity' => $severity,
            'user_id' => \XF::visitor()->user_id,
            'ip_address' => \XF::app()->request()->getIp(),
            'user_agent' => \XF::app()->request()->getServer('HTTP_USER_AGENT', ''),
            'timestamp' => \XF::$time
        ];

        // Store in XF error log for now
        \XF::logError('[Affiliate Security] ' . $event . ': ' . json_encode($logData));
        
        // TODO: Store in dedicated security log table when implemented
    }

    /**
     * Validates slug format
     */
    public static function validateSlug($slug)
    {
        if (empty($slug)) {
            return false;
        }

        // Allow various slug formats but prevent XSS
        $slug = self::sanitizeInput($slug);
        
        // Check for reasonable length
        if (strlen($slug) > 1000) {
            return false;
        }

        // Block obvious XSS attempts
        if (preg_match('/<script|javascript:|data:|vbscript:/i', $slug)) {
            return false;
        }

        return true;
    }

    /**
     * Get allowed domains for affiliate links
     */
    public static function getAllowedDomains()
    {
        $options = \XF::app()->options();
        $domains = $options->hardmob_afiliados_allowed_domains ?? '';
        
        if (empty($domains)) {
            // Default allowed domains for major affiliate programs
            return [
                'amazon.com', 'amazon.com.br', 'amazon.co.uk',
                'mercadolivre.com.br', 'mercadolibre.com',
                'shopee.com.br', 'shopee.com',
                'americanas.com.br', 'submarino.com.br',
                'magazineluiza.com.br', 'casasbahia.com.br',
                'extra.com.br', 'pontofrio.com.br'
            ];
        }

        return array_filter(array_map('trim', explode("\n", $domains)));
    }
}