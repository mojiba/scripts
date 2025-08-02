<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../afiliados/Service/AffiliateGenerator.php';

class AffiliateGeneratorTest extends TestCase
{
    protected $app;
    protected $generator;

    public function __construct()
    {
        parent::__construct();
        $this->app = new MockApp();
        
        // Create a testable version of AffiliateGenerator
        $this->generator = new class($this->app) extends \hardMOB\Afiliados\Service\AffiliateGenerator {
            public function __construct($app)
            {
                parent::__construct($app);
            }

            // Make protected methods public for testing
            public function testDetectStoreFromSlug($slug)
            {
                return $this->detectStoreFromSlug($slug);
            }

            public function testBuildPublicLink($storeId, $slug)
            {
                return $this->buildPublicLink($storeId, $slug);
            }

            public function testGenerateAffiliateLink($slug, $userId = null)
            {
                return $this->generateAffiliateLink($slug, $userId);
            }

            public function testGetTestSlugs($store)
            {
                return $this->getTestSlugs($store);
            }
        };
    }

    public function testProcessTextWithSinglePlaceholder()
    {
        $text = "Confira este produto: {{slug:/dp/B08N5WRWNW}}";
        $result = $this->generator->processText($text);
        
        $this->assertStringContains('/affiliate/', $result, 'Processed text contains affiliate link');
        $this->assertStringContains('B08N5WRWNW', $result, 'Processed text contains original slug encoded');
    }

    public function testProcessTextWithMultiplePlaceholders()
    {
        $text = "Amazon: {{slug:/dp/B08N5WRWNW}} e ML: {{slug:MLB-123456789}}";
        $result = $this->generator->processText($text);
        
        $this->assertStringContains('/affiliate/', $result, 'Result contains affiliate links');
        // Should replace both placeholders
        $placeholderCount = substr_count($text, '{{slug:');
        $linkCount = substr_count($result, '/affiliate/');
        $this->assertEquals($placeholderCount, $linkCount, 'All placeholders were replaced with affiliate links');
    }

    public function testProcessTextWithoutPlaceholders()
    {
        $text = "Este é um texto normal sem placeholders";
        $result = $this->generator->processText($text);
        
        $this->assertEquals($text, $result, 'Text without placeholders remains unchanged');
    }

    public function testDetectStoreFromAmazonASIN()
    {
        // Test with Amazon ASIN
        $store = $this->generator->testDetectStoreFromSlug('B08N5WRWNW');
        $this->assertTrue($store !== null, 'Amazon ASIN detected a store');
    }

    public function testDetectStoreFromMercadoLivreMLB()
    {
        $store = $this->generator->testDetectStoreFromSlug('MLB-123456789');
        $this->assertTrue($store !== null, 'MercadoLivre MLB detected a store');
    }

    public function testDetectStoreFromShopeePattern()
    {
        $store = $this->generator->testDetectStoreFromSlug('produto-teste-i.123.456');
        $this->assertTrue($store !== null, 'Shopee pattern detected a store');
    }

    public function testDetectStoreFromFullURL()
    {
        $store = $this->generator->testDetectStoreFromSlug('https://amazon.com.br/dp/B08N5WRWNW');
        $this->assertTrue($store !== null, 'Full Amazon URL detected a store');
    }

    public function testBuildPublicLink()
    {
        $link = $this->generator->testBuildPublicLink(1, 'test-slug');
        
        $this->assertStringContains('/affiliate/', $link, 'Public link contains affiliate path');
        $this->assertStringContains('1', $link, 'Public link contains store ID');
    }

    public function testGenerateAffiliateLink()
    {
        $link = $this->generator->testGenerateAffiliateLink('B08N5WRWNW');
        
        $this->assertIsString($link, 'Generated link is a string');
        $this->assertTrue(strlen($link) > 0, 'Generated link is not empty');
    }

    public function testPreGenerateLinks()
    {
        $count = $this->generator->preGenerateLinks();
        
        $this->assertIsNumeric($count, 'Pre-generate returns numeric count');
        $this->assertTrue($count >= 0, 'Pre-generate count is non-negative');
    }

    public function testGetTestSlugs()
    {
        $amazonStore = new MockStore(['name' => 'Amazon']);
        $slugs = $this->generator->testGetTestSlugs($amazonStore);
        
        $this->assertIsArray($slugs, 'Test slugs returns an array');
        $this->assertTrue(count($slugs) > 0, 'Test slugs array is not empty');
        $this->assertStringContains('B0', $slugs[0], 'Amazon test slugs contain ASIN patterns');
    }

    public function testInvalidSlugHandling()
    {
        $text = "Invalid: {{slug:}}";
        $result = $this->generator->processText($text);
        
        // Should handle empty slugs gracefully
        $this->assertIsString($result, 'Invalid slug handling returns string');
    }

    public function testUserIdPassedToGeneration()
    {
        $text = "Test: {{slug:B08N5WRWNW}}";
        $userId = 123;
        $result = $this->generator->processText($text, $userId);
        
        $this->assertIsString($result, 'Text processing with user ID works');
        $this->assertStringContains('/affiliate/', $result, 'User ID parameter does not break link generation');
    }
}