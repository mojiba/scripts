<?php

namespace hardMOB\Afiliados\Tests;

/**
 * Simple test utilities for the affiliate system
 * Note: These are basic validation tests, not full unit tests
 */
class SecurityTests
{
    public static function runValidationTests()
    {
        $results = [];
        
        // Test URL validation
        $results['url_validation'] = self::testUrlValidation();
        
        // Test domain validation  
        $results['domain_validation'] = self::testDomainValidation();
        
        // Test slug validation
        $results['slug_validation'] = self::testSlugValidation();
        
        // Test affiliate code validation
        $results['affiliate_validation'] = self::testAffiliateValidation();
        
        return $results;
    }
    
    protected static function testUrlValidation()
    {
        $validUrls = [
            'https://amazon.com.br/produto/123',
            'http://mercadolivre.com.br/item/456',
            'https://shopee.com.br/produto-teste'
        ];
        
        $invalidUrls = [
            'javascript:alert(1)',
            'data:text/html,<script>alert(1)</script>',
            'ftp://malicious.com',
            'file:///etc/passwd',
            ''
        ];
        
        $passed = 0;
        $total = count($validUrls) + count($invalidUrls);
        
        foreach ($validUrls as $url) {
            if (\hardMOB\Afiliados\Helper\Security::validateUrl($url, ['amazon.com.br', 'mercadolivre.com.br', 'shopee.com.br'])) {
                $passed++;
            }
        }
        
        foreach ($invalidUrls as $url) {
            if (!\hardMOB\Afiliados\Helper\Security::validateUrl($url)) {
                $passed++;
            }
        }
        
        return [
            'passed' => $passed,
            'total' => $total,
            'success' => $passed === $total
        ];
    }
    
    protected static function testDomainValidation()
    {
        $validDomains = [
            'amazon.com.br',
            'mercadolivre.com.br',
            'shopee.com.br',
            'google.com'
        ];
        
        $invalidDomains = [
            'localhost',
            '127.0.0.1',
            '192.168.1.1',
            'invalid',
            '',
            '<script>alert(1)</script>'
        ];
        
        $passed = 0;
        $total = count($validDomains) + count($invalidDomains);
        
        foreach ($validDomains as $domain) {
            if (\hardMOB\Afiliados\Helper\Security::validateDomain($domain)) {
                $passed++;
            }
        }
        
        foreach ($invalidDomains as $domain) {
            if (!\hardMOB\Afiliados\Helper\Security::validateDomain($domain)) {
                $passed++;
            }
        }
        
        return [
            'passed' => $passed,
            'total' => $total,
            'success' => $passed === $total
        ];
    }
    
    protected static function testSlugValidation()
    {
        $validSlugs = [
            '/produto/123',
            'item-exemplo',
            'https://amazon.com.br/dp/B123456789',
            'MLB-123456789'
        ];
        
        $invalidSlugs = [
            '<script>alert(1)</script>',
            'javascript:alert(1)',
            'data:text/html,<script>',
            '',
            str_repeat('a', 1001) // Too long
        ];
        
        $passed = 0;
        $total = count($validSlugs) + count($invalidSlugs);
        
        foreach ($validSlugs as $slug) {
            if (\hardMOB\Afiliados\Helper\Security::validateSlug($slug)) {
                $passed++;
            }
        }
        
        foreach ($invalidSlugs as $slug) {
            if (!\hardMOB\Afiliados\Helper\Security::validateSlug($slug)) {
                $passed++;
            }
        }
        
        return [
            'passed' => $passed,
            'total' => $total,
            'success' => $passed === $total
        ];
    }
    
    protected static function testAffiliateValidation()
    {
        $validCodes = [
            'amazon-123',
            'affiliate_code',
            'abc123',
            'test-code-1'
        ];
        
        $invalidCodes = [
            '',
            'ab', // Too short
            str_repeat('a', 51), // Too long
            '<script>',
            'code with spaces',
            'código-inválido!' // Special chars
        ];
        
        $passed = 0;
        $total = count($validCodes) + count($invalidCodes);
        
        foreach ($validCodes as $code) {
            if (\hardMOB\Afiliados\Helper\Security::validateAffiliateCode($code)) {
                $passed++;
            }
        }
        
        foreach ($invalidCodes as $code) {
            if (!\hardMOB\Afiliados\Helper\Security::validateAffiliateCode($code)) {
                $passed++;
            }
        }
        
        return [
            'passed' => $passed,
            'total' => $total,
            'success' => $passed === $total
        ];
    }
}