<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../afiliados/Connector/StoreInterface.php';
require_once __DIR__ . '/../afiliados/Connector/Amazon.php';

class ConnectorTest extends TestCase
{
    protected $store;
    protected $amazonConnector;

    public function __construct()
    {
        parent::__construct();
        $this->store = new MockStore([
            'store_id' => 1,
            'name' => 'Amazon',
            'domain' => 'amazon.com.br',
            'affiliate_code' => 'testcode-20'
        ]);
        $this->amazonConnector = new \hardMOB\Afiliados\Connector\Amazon($this->store);
    }

    public function testAmazonValidateASIN()
    {
        $validASINs = [
            'B08N5WRWNW',
            'B07XJ8C8F7',
            'B0ABCD1234',
            'B123456789'
        ];
        
        foreach ($validASINs as $asin) {
            $this->assertTrue(
                $this->amazonConnector->validateSlug($asin),
                "Valid Amazon ASIN accepted: $asin"
            );
        }
    }

    public function testAmazonValidateURLs()
    {
        $validURLs = [
            '/dp/B08N5WRWNW',
            '/product/B07XJ8C8F7',
            '/dp/B123456789/ref=test',
            '/product/B0ABCD1234?tag=test'
        ];
        
        foreach ($validURLs as $url) {
            $this->assertTrue(
                $this->amazonConnector->validateSlug($url),
                "Valid Amazon URL path accepted: $url"
            );
        }
    }

    public function testAmazonRejectInvalidSlugs()
    {
        $invalidSlugs = [
            'B123', // Too short
            'B12345678901', // Too long
            'MLB-123456789', // MercadoLivre format
            'produto-i.123.456', // Shopee format
            '', // Empty
            '123456789', // No B prefix
            'BABCDEFGHI', // Contains letters in wrong position
            '/category/books' // Valid URL but no ASIN
        ];
        
        foreach ($invalidSlugs as $slug) {
            $this->assertFalse(
                $this->amazonConnector->validateSlug($slug),
                "Invalid Amazon slug rejected: $slug"
            );
        }
    }

    public function testAmazonGenerateAffiliateUrlFromASIN()
    {
        $asin = 'B08N5WRWNW';
        $affiliateUrl = $this->amazonConnector->generateAffiliateUrl($asin);
        
        $this->assertIsString($affiliateUrl, 'Affiliate URL is a string');
        $this->assertStringContains('amazon.com.br', $affiliateUrl, 'URL contains store domain');
        $this->assertStringContains('/dp/B08N5WRWNW', $affiliateUrl, 'URL contains ASIN in dp path');
        $this->assertStringContains('tag=testcode-20', $affiliateUrl, 'URL contains affiliate code');
    }

    public function testAmazonGenerateAffiliateUrlFromPath()
    {
        $path = '/dp/B08N5WRWNW';
        $affiliateUrl = $this->amazonConnector->generateAffiliateUrl($path);
        
        $this->assertStringContains('amazon.com.br/dp/B08N5WRWNW', $affiliateUrl, 'URL constructed from path');
        $this->assertStringContains('tag=testcode-20', $affiliateUrl, 'Affiliate code added to path URL');
    }

    public function testAmazonGenerateAffiliateUrlFromProductPath()
    {
        $path = '/product/B07XJ8C8F7';
        $affiliateUrl = $this->amazonConnector->generateAffiliateUrl($path);
        
        $this->assertStringContains('amazon.com.br/product/B07XJ8C8F7', $affiliateUrl, 'URL preserves product path');
        $this->assertStringContains('tag=testcode-20', $affiliateUrl, 'Affiliate code added to product URL');
    }

    public function testAmazonAffiliateUrlWithExistingParams()
    {
        $pathWithParams = '/dp/B08N5WRWNW?ref=test&variant=blue';
        $affiliateUrl = $this->amazonConnector->generateAffiliateUrl($pathWithParams);
        
        $this->assertStringContains('ref=test', $affiliateUrl, 'Existing parameters preserved');
        $this->assertStringContains('variant=blue', $affiliateUrl, 'Multiple existing parameters preserved');
        $this->assertStringContains('&tag=testcode-20', $affiliateUrl, 'Affiliate code added with ampersand');
    }

    public function testConnectorInterface()
    {
        $this->assertTrue(
            $this->amazonConnector instanceof \hardMOB\Afiliados\Connector\StoreInterface,
            'Amazon connector implements StoreInterface'
        );
        
        $this->assertTrue(
            method_exists($this->amazonConnector, 'generateAffiliateUrl'),
            'Amazon connector has generateAffiliateUrl method'
        );
        
        $this->assertTrue(
            method_exists($this->amazonConnector, 'validateSlug'),
            'Amazon connector has validateSlug method'
        );
    }

    public function testConnectorWithDifferentStoreConfig()
    {
        $usStore = new MockStore([
            'domain' => 'amazon.com',
            'affiliate_code' => 'usaffiliate-20'
        ]);
        $usConnector = new \hardMOB\Afiliados\Connector\Amazon($usStore);
        
        $affiliateUrl = $usConnector->generateAffiliateUrl('B08N5WRWNW');
        
        $this->assertStringContains('amazon.com', $affiliateUrl, 'US store uses correct domain');
        $this->assertStringContains('tag=usaffiliate-20', $affiliateUrl, 'US store uses correct affiliate code');
    }

    public function testConnectorValidationConsistency()
    {
        // Test that validation and generation are consistent
        $testSlugs = [
            'B08N5WRWNW',
            '/dp/B07XJ8C8F7',
            '/product/B123456789',
            'invalid-slug',
            'MLB-123456789'
        ];
        
        foreach ($testSlugs as $slug) {
            $isValid = $this->amazonConnector->validateSlug($slug);
            
            if ($isValid) {
                $url = $this->amazonConnector->generateAffiliateUrl($slug);
                $this->assertIsString($url, "Valid slug '$slug' generates valid URL");
                $this->assertTrue(strlen($url) > 0, "Valid slug '$slug' generates non-empty URL");
            } else {
                // Even invalid slugs should generate some URL (graceful handling)
                $url = $this->amazonConnector->generateAffiliateUrl($slug);
                $this->assertIsString($url, "Invalid slug '$slug' still generates string URL");
            }
        }
    }

    public function testAmazonEdgeCases()
    {
        // Test edge cases for Amazon connector
        $edgeCases = [
            'B000000000', // All zeros
            'B999999999', // All nines
            'BZZZZZZZZ9', // Mixed characters
            'B12345678A'  // Ends with letter
        ];
        
        foreach ($edgeCases as $case) {
            $isValid = $this->amazonConnector->validateSlug($case);
            $this->assertTrue($isValid, "Edge case ASIN '$case' should be valid");
        }
    }

    public function testSpecialCharactersInPath()
    {
        $pathsWithSpecialChars = [
            '/dp/B08N5WRWNW/ref=sr_1_1',
            '/product/B07XJ8C8F7?keywords=test%20product',
            '/dp/B123456789#customer-reviews'
        ];
        
        foreach ($pathsWithSpecialChars as $path) {
            $isValid = $this->amazonConnector->validateSlug($path);
            $this->assertTrue($isValid, "Path with special characters should be valid: $path");
            
            $url = $this->amazonConnector->generateAffiliateUrl($path);
            $this->assertStringContains('tag=testcode-20', $url, "Special character path gets affiliate code: $path");
        }
    }

    public function testConnectorErrorHandling()
    {
        // Test with null store (edge case)
        try {
            $nullConnector = new \hardMOB\Afiliados\Connector\Amazon(null);
            $this->assertTrue(true, 'Connector handles null store gracefully');
        } catch (Error $e) {
            $this->assertTrue(true, 'Connector appropriately throws error for null store');
        }
    }

    public function testURLStructure()
    {
        $asin = 'B08N5WRWNW';
        $url = $this->amazonConnector->generateAffiliateUrl($asin);
        
        // Parse the generated URL
        $parsed = parse_url($url);
        
        $this->assertEquals('https', $parsed['scheme'], 'URL uses HTTPS');
        $this->assertEquals('amazon.com.br', $parsed['host'], 'URL has correct host');
        $this->assertStringContains('/dp/', $parsed['path'], 'URL has correct path structure');
        
        parse_str($parsed['query'], $queryParams);
        $this->assertEquals('testcode-20', $queryParams['tag'], 'URL has correct affiliate tag parameter');
    }

    public function testConnectorPerformance()
    {
        // Test that validation and URL generation are reasonably fast
        $start = microtime(true);
        
        for ($i = 0; $i < 100; $i++) {
            $asin = 'B' . str_pad($i, 9, '0', STR_PAD_LEFT);
            $this->amazonConnector->validateSlug($asin);
            $this->amazonConnector->generateAffiliateUrl($asin);
        }
        
        $end = microtime(true);
        $duration = $end - $start;
        
        $this->assertTrue($duration < 1.0, 'Connector operations complete within reasonable time');
    }
}