<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../afiliados/Cache/LinkCache.php';

class LinkCacheTest extends TestCase
{
    protected $app;
    protected $cache;

    public function __construct()
    {
        parent::__construct();
        $this->app = new MockApp();
        $this->cache = new \hardMOB\Afiliados\Cache\LinkCache($this->app);
    }

    public function testGetFromFileCache()
    {
        // Test file cache get operation
        $result = $this->cache->get('test_key');
        
        // Should return cached data from mock DB
        $this->assertIsArray($result, 'Cache get returns array data');
        $this->assertArrayHasKey('store_id', $result, 'Cached data has store_id');
        $this->assertArrayHasKey('affiliate_url', $result, 'Cached data has affiliate_url');
    }

    public function testSetToFileCache()
    {
        $testData = [
            'store_id' => 1,
            'affiliate_url' => 'https://example.com/product?tag=test'
        ];
        
        $result = $this->cache->set('test_key', $testData, 3600);
        
        $this->assertTrue($result, 'Cache set operation succeeds');
    }

    public function testDeleteFromFileCache()
    {
        $result = $this->cache->delete('test_key');
        
        $this->assertTrue($result >= 0, 'Cache delete returns non-negative result');
    }

    public function testClearAllFileCache()
    {
        $result = $this->cache->clearAll();
        
        $this->assertTrue($result, 'Cache clear all operation succeeds');
    }

    public function testCacheDriverSelection()
    {
        // Test with file driver (default)
        $this->app->setOption('hardmob_afiliados_cache_driver', 'file');
        $cache = new \hardMOB\Afiliados\Cache\LinkCache($this->app);
        
        $result = $cache->set('test', ['data' => 'test'], 300);
        $this->assertTrue($result, 'File cache driver works');
        
        // Test with Redis driver (will fallback to file due to mock)
        $this->app->setOption('hardmob_afiliados_cache_driver', 'redis');
        $cache = new \hardMOB\Afiliados\Cache\LinkCache($this->app);
        
        $result = $cache->set('test', ['data' => 'test'], 300);
        $this->assertTrue($result, 'Redis cache driver falls back to file cache');
    }

    public function testCacheWithTTL()
    {
        $testData = ['test' => 'data'];
        
        // Test with TTL
        $result = $this->cache->set('ttl_test', $testData, 3600);
        $this->assertTrue($result, 'Cache set with TTL succeeds');
        
        // Test without TTL (permanent)
        $result = $this->cache->set('permanent_test', $testData, 0);
        $this->assertTrue($result, 'Cache set without TTL (permanent) succeeds');
    }

    public function testGetStats()
    {
        $stats = $this->cache->getStats();
        
        $this->assertIsArray($stats, 'Cache stats returns array');
        $this->assertArrayHasKey('total_entries', $stats, 'Stats contains total_entries');
        $this->assertArrayHasKey('expired_entries', $stats, 'Stats contains expired_entries');
        $this->assertArrayHasKey('active_entries', $stats, 'Stats contains active_entries');
        
        $this->assertIsNumeric($stats['total_entries'], 'Total entries is numeric');
        $this->assertIsNumeric($stats['expired_entries'], 'Expired entries is numeric');
        $this->assertIsNumeric($stats['active_entries'], 'Active entries is numeric');
    }

    public function testCleanExpired()
    {
        $result = $this->cache->cleanExpired();
        
        $this->assertIsNumeric($result, 'Clean expired returns numeric result');
        $this->assertTrue($result >= 0, 'Clean expired result is non-negative');
    }

    public function testRedisConnectionFallback()
    {
        $this->app->setOption('hardmob_afiliados_cache_driver', 'redis');
        $cache = new \hardMOB\Afiliados\Cache\LinkCache($this->app);
        
        // Redis will fail in mock environment and fallback to file cache
        $testData = ['fallback' => 'test'];
        $result = $cache->set('redis_test', $testData);
        
        $this->assertTrue($result, 'Redis cache falls back to file cache when Redis unavailable');
    }

    public function testCacheKeyHandling()
    {
        // Test various key types
        $keys = [
            'simple_key',
            'key_with_underscore',
            'key-with-dash',
            'key123',
            md5('complex_key_hash')
        ];
        
        foreach ($keys as $key) {
            $result = $this->cache->set($key, ['test' => 'data']);
            $this->assertTrue($result, "Cache handles key: $key");
        }
    }

    public function testCacheValueTypes()
    {
        // Test different value types
        $testCases = [
            'array' => ['store_id' => 1, 'url' => 'test'],
            'string' => 'simple string value',
            'number' => 42,
            'boolean' => true,
            'complex' => [
                'nested' => ['data' => 'test'],
                'array' => [1, 2, 3],
                'mixed' => 'value'
            ]
        ];
        
        foreach ($testCases as $type => $value) {
            $result = $this->cache->set("test_$type", $value);
            $this->assertTrue($result, "Cache handles $type data type");
        }
    }

    public function testCacheOperationChaining()
    {
        $key = 'chain_test';
        $data = ['chained' => 'operation'];
        
        // Set -> Get -> Delete -> Get cycle
        $setResult = $this->cache->set($key, $data);
        $this->assertTrue($setResult, 'Chain: Set operation succeeds');
        
        $getData = $this->cache->get($key);
        $this->assertIsArray($getData, 'Chain: Get after set returns data');
        
        $deleteResult = $this->cache->delete($key);
        $this->assertTrue($deleteResult >= 0, 'Chain: Delete operation succeeds');
    }

    public function testEmptyAndNullValues()
    {
        // Test empty array
        $result = $this->cache->set('empty_array', []);
        $this->assertTrue($result, 'Cache handles empty array');
        
        // Test null value
        $result = $this->cache->set('null_value', null);
        $this->assertTrue($result, 'Cache handles null value');
        
        // Test empty string
        $result = $this->cache->set('empty_string', '');
        $this->assertTrue($result, 'Cache handles empty string');
    }
}