<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../afiliados/Helper/LinkParser.php';

class LinkParserTest extends TestCase
{
    protected $app;
    protected $parser;

    public function __construct()
    {
        parent::__construct();
        $this->app = new MockApp();
        $this->parser = new \hardMOB\Afiliados\Helper\LinkParser($this->app);
    }

    public function testExtractProductInfoFromAmazonURL()
    {
        $url = 'https://amazon.com.br/dp/B08N5WRWNW';
        $info = $this->parser->extractProductInfo($url);
        
        $this->assertIsArray($info, 'Extract product info returns array');
        $this->assertEquals('amazon.com.br', $info['domain'], 'Correctly extracts Amazon domain');
        $this->assertEquals('amazon', $info['type'], 'Correctly identifies Amazon type');
        $this->assertEquals('B08N5WRWNW', $info['product_id'], 'Correctly extracts Amazon ASIN');
    }

    public function testExtractProductInfoFromMercadoLivreURL()
    {
        $url = 'https://mercadolivre.com.br/produto/MLB-123456789';
        $info = $this->parser->extractProductInfo($url);
        
        $this->assertEquals('mercadolivre.com.br', $info['domain'], 'Correctly extracts MercadoLivre domain');
        $this->assertEquals('mercadolivre', $info['type'], 'Correctly identifies MercadoLivre type');
        $this->assertEquals('MLB-123456789', $info['product_id'], 'Correctly extracts MLB ID');
    }

    public function testExtractProductInfoFromShopeeURL()
    {
        $url = 'https://shopee.com.br/produto-teste-i.123.456';
        $info = $this->parser->extractProductInfo($url);
        
        $this->assertEquals('shopee.com.br', $info['domain'], 'Correctly extracts Shopee domain');
        $this->assertEquals('shopee', $info['type'], 'Correctly identifies Shopee type');
        $this->assertStringContains('i.123.456', $info['product_id'], 'Correctly extracts Shopee product ID');
    }

    public function testExtractProductInfoFromUnknownURL()
    {
        $url = 'https://unknown-store.com/product/123';
        $info = $this->parser->extractProductInfo($url);
        
        $this->assertEquals('unknown-store.com', $info['domain'], 'Extracts domain from unknown store');
        $this->assertEquals('unknown', $info['type'], 'Unknown store type is "unknown"');
        $this->assertNull($info['product_id'], 'Unknown store has null product_id');
    }

    public function testExtractProductInfoFromInvalidURL()
    {
        $url = 'not-a-valid-url';
        $info = $this->parser->extractProductInfo($url);
        
        $this->assertNull($info['domain'], 'Invalid URL returns null domain');
        $this->assertEquals('unknown', $info['type'], 'Invalid URL has unknown type');
        $this->assertNull($info['product_id'], 'Invalid URL has null product_id');
    }

    public function testIsAffiliateURL()
    {
        $affiliateURLs = [
            'https://amazon.com.br/dp/B123?tag=affiliate',
            'https://example.com/product?affiliate_id=123',
            'https://store.com/item?ref=partner',
            'https://shop.com/product?utm_source=affiliate',
            'https://example.com/product?partner=123',
            'https://store.com/item?aff_id=456',
            'https://shop.com/product?affid=789',
            'https://example.com/item?affiliate=test'
        ];
        
        foreach ($affiliateURLs as $url) {
            $this->assertTrue(
                $this->parser->isAffiliateUrl($url),
                "URL correctly identified as affiliate: $url"
            );
        }
    }

    public function testIsNotAffiliateURL()
    {
        $nonAffiliateURLs = [
            'https://amazon.com.br/dp/B123',
            'https://example.com/product',
            'https://store.com/item?id=123',
            'https://shop.com/product?color=red&size=large'
        ];
        
        foreach ($nonAffiliateURLs as $url) {
            $this->assertFalse(
                $this->parser->isAffiliateUrl($url),
                "URL correctly identified as non-affiliate: $url"
            );
        }
    }

    public function testCleanURL()
    {
        $dirtyUrl = 'https://amazon.com.br/dp/B123?utm_source=google&utm_campaign=test&ref=partner&tag=affiliate&color=red';
        $cleanUrl = $this->parser->cleanUrl($dirtyUrl);
        
        $this->assertStringContains('amazon.com.br/dp/B123', $cleanUrl, 'Clean URL preserves base URL');
        $this->assertStringContains('color=red', $cleanUrl, 'Clean URL preserves non-tracking parameters');
        $this->assertStringNotContains('utm_source', $cleanUrl, 'Clean URL removes utm_source');
        $this->assertStringNotContains('utm_campaign', $cleanUrl, 'Clean URL removes utm_campaign');
        $this->assertStringNotContains('ref=', $cleanUrl, 'Clean URL removes ref parameter');
        $this->assertStringNotContains('tag=', $cleanUrl, 'Clean URL removes tag parameter');
    }

    public function testCleanURLWithoutTracking()
    {
        $cleanUrl = 'https://example.com/product?id=123&color=blue';
        $result = $this->parser->cleanUrl($cleanUrl);
        
        $this->assertEquals($cleanUrl, $result, 'Clean URL without tracking params remains unchanged');
    }

    public function testCleanURLWithoutQuery()
    {
        $simpleUrl = 'https://example.com/product';
        $result = $this->parser->cleanUrl($simpleUrl);
        
        $this->assertEquals($simpleUrl, $result, 'Simple URL without query remains unchanged');
    }

    public function testValidateValidURLs()
    {
        $validURLs = [
            'https://amazon.com.br/dp/B123',
            'http://example.com/product',
            'https://mercadolivre.com.br/MLB-123',
            'http://shopee.com.br/item-i.1.2',
            'https://subdomain.example.com/path'
        ];
        
        foreach ($validURLs as $url) {
            $this->assertTrue(
                $this->parser->validateUrl($url),
                "URL correctly validated as valid: $url"
            );
        }
    }

    public function testValidateInvalidURLs()
    {
        $invalidURLs = [
            'not-a-url',
            'ftp://example.com/file', // Non-HTTP/HTTPS
            'javascript:alert("test")', // JavaScript protocol
            'http://', // Missing host
            '', // Empty string
            'https://', // HTTPS without host
            'file:///path/to/file' // File protocol
        ];
        
        foreach ($invalidURLs as $url) {
            $this->assertFalse(
                $this->parser->validateUrl($url),
                "URL correctly validated as invalid: $url"
            );
        }
    }

    public function testExtractAmazonVariations()
    {
        $amazonURLs = [
            'https://amazon.com.br/dp/B08N5WRWNW',
            'https://www.amazon.com.br/produto/dp/B08N5WRWNW/ref=test',
            'https://amazon.com/dp/B08N5WRWNW?tag=test',
            'https://amazon.co.uk/dp/B08N5WRWNW'
        ];
        
        foreach ($amazonURLs as $url) {
            $info = $this->parser->extractProductInfo($url);
            $this->assertEquals('amazon', $info['type'], "Amazon URL type detected: $url");
            $this->assertEquals('B08N5WRWNW', $info['product_id'], "Amazon ASIN extracted: $url");
        }
    }

    public function testExtractMercadoLivreVariations()
    {
        $mlURLs = [
            'https://mercadolivre.com.br/produto/MLB-123456789',
            'https://produto.mercadolivre.com.br/MLB-987654321',
            'https://mercadolivre.com.br/p/MLB-111222333'
        ];
        
        foreach ($mlURLs as $url) {
            $info = $this->parser->extractProductInfo($url);
            $this->assertEquals('mercadolivre', $info['type'], "MercadoLivre URL type detected: $url");
            $this->assertStringContains('MLB-', $info['product_id'], "MLB ID extracted: $url");
        }
    }

    public function testExtractShopeeVariations()
    {
        $shopeeURLs = [
            'https://shopee.com.br/produto-teste-i.123.456',
            'https://shopee.com.br/categoria/item-name-i.789.012',
            'https://br.shopee.com/special-product-i.111.222'
        ];
        
        foreach ($shopeeURLs as $url) {
            $info = $this->parser->extractProductInfo($url);
            $this->assertEquals('shopee', $info['type'], "Shopee URL type detected: $url");
            $this->assertStringContains('-i.', $info['product_id'], "Shopee ID pattern extracted: $url");
        }
    }

    public function testCleanURLEdgeCases()
    {
        // URL with only tracking parameters
        $trackingOnlyUrl = 'https://example.com?utm_source=test&utm_campaign=affiliate&ref=partner';
        $cleaned = $this->parser->cleanUrl($trackingOnlyUrl);
        $this->assertEquals('https://example.com', $cleaned, 'URL with only tracking params gets cleaned to base URL');
        
        // URL with mixed parameters
        $mixedUrl = 'https://example.com/path?id=123&utm_source=test&color=red&tag=affiliate&size=large';
        $cleaned = $this->parser->cleanUrl($mixedUrl);
        $this->assertStringContains('id=123', $cleaned, 'Mixed URL preserves essential params');
        $this->assertStringContains('color=red', $cleaned, 'Mixed URL preserves product params');
        $this->assertStringContains('size=large', $cleaned, 'Mixed URL preserves filter params');
        $this->assertStringNotContains('utm_source', $cleaned, 'Mixed URL removes tracking params');
        $this->assertStringNotContains('tag=', $cleaned, 'Mixed URL removes affiliate params');
    }

    public function testParseInvalidInputs()
    {
        $invalidInputs = [null, '', false, 0];
        
        foreach ($invalidInputs as $input) {
            // Should not throw exceptions
            $info = $this->parser->extractProductInfo($input);
            $this->assertIsArray($info, 'Invalid input returns array structure');
            $this->assertEquals('unknown', $info['type'], 'Invalid input has unknown type');
        }
    }

    public function testURLWithFragments()
    {
        $urlWithFragment = 'https://amazon.com.br/dp/B08N5WRWNW#reviews';
        $info = $this->parser->extractProductInfo($urlWithFragment);
        
        $this->assertEquals('amazon', $info['type'], 'URL with fragment correctly parsed');
        $this->assertEquals('B08N5WRWNW', $info['product_id'], 'Product ID extracted despite fragment');
    }

    public function testURLWithPorts()
    {
        $urlWithPort = 'https://example.com:8080/product/123';
        $info = $this->parser->extractProductInfo($urlWithPort);
        
        $this->assertEquals('example.com:8080', $info['domain'], 'Domain with port correctly extracted');
        $this->assertTrue($this->parser->validateUrl($urlWithPort), 'URL with port is valid');
    }
}