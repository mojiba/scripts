<?php

/**
 * Simple Test Framework for hardMOB Afiliados
 * Provides basic testing functionality without external dependencies
 */

// XenForo framework mock classes - must be declared before regular classes
namespace XF\Service {
    class AbstractService
    {
        protected $app;

        public function __construct($app = null)
        {
            $this->app = $app;
        }

        protected function app()
        {
            return $this->app;
        }

        protected function repository($repoClass)
        {
            return $this->app->repository($repoClass);
        }

        protected function em()
        {
            return $this->app->em();
        }

        protected function db()
        {
            return $this->app->db();
        }
    }
}

namespace XF {
    class App
    {
        // Mock XF\App class for type hints
    }
}

namespace hardMOB\Afiliados\Entity {
    class Store
    {
        // Mock Store entity for type hints
    }
}

namespace {

class TestCase
{
    protected $testName = '';
    protected $assertions = 0;
    protected $passed = 0;
    protected $failed = 0;

    public function __construct()
    {
        $this->testName = get_class($this);
    }

    protected function assertTrue($condition, $message = '')
    {
        $this->assertions++;
        if ($condition) {
            $this->passed++;
            echo "✓ " . ($message ?: 'Assertion passed') . "\n";
        } else {
            $this->failed++;
            echo "✗ " . ($message ?: 'Assertion failed') . "\n";
        }
    }

    protected function assertFalse($condition, $message = '')
    {
        $this->assertTrue(!$condition, $message);
    }

    protected function assertEquals($expected, $actual, $message = '')
    {
        $this->assertions++;
        if ($expected === $actual) {
            $this->passed++;
            echo "✓ " . ($message ?: "Expected '$expected', got '$actual'") . "\n";
        } else {
            $this->failed++;
            echo "✗ " . ($message ?: "Expected '$expected', got '$actual'") . "\n";
        }
    }

    protected function assertNotEquals($expected, $actual, $message = '')
    {
        $this->assertions++;
        if ($expected !== $actual) {
            $this->passed++;
            echo "✓ " . ($message ?: "Values are different as expected") . "\n";
        } else {
            $this->failed++;
            echo "✗ " . ($message ?: "Expected values to be different, but both are '$expected'") . "\n";
        }
    }

    protected function assertArrayHasKey($key, $array, $message = '')
    {
        $this->assertions++;
        if (array_key_exists($key, $array)) {
            $this->passed++;
            echo "✓ " . ($message ?: "Array has key '$key'") . "\n";
        } else {
            $this->failed++;
            echo "✗ " . ($message ?: "Array does not have key '$key'") . "\n";
        }
    }

    protected function assertStringContains($needle, $haystack, $message = '')
    {
        $this->assertions++;
        if (strpos($haystack, $needle) !== false) {
            $this->passed++;
            echo "✓ " . ($message ?: "String contains '$needle'") . "\n";
        } else {
            $this->failed++;
            echo "✗ " . ($message ?: "String does not contain '$needle'") . "\n";
        }
    }

    protected function assertIsArray($actual, $message = '')
    {
        $this->assertTrue(is_array($actual), $message ?: 'Value is an array');
    }

    protected function assertIsString($actual, $message = '')
    {
        $this->assertTrue(is_string($actual), $message ?: 'Value is a string');
    }

    protected function assertIsNumeric($actual, $message = '')
    {
        $this->assertTrue(is_numeric($actual), $message ?: 'Value is numeric');
    }

    protected function assertNull($actual, $message = '')
    {
        $this->assertTrue($actual === null, $message ?: 'Value is null');
    }

    protected function assertNotNull($actual, $message = '')
    {
        $this->assertTrue($actual !== null, $message ?: 'Value is not null');
    }

    protected function assertStringNotContains($needle, $haystack, $message = '')
    {
        $this->assertions++;
        if (strpos($haystack, $needle) === false) {
            $this->passed++;
            echo "✓ " . ($message ?: "String does not contain '$needle'") . "\n";
        } else {
            $this->failed++;
            echo "✗ " . ($message ?: "String contains '$needle' but shouldn't") . "\n";
        }
    }

    public function run()
    {
        echo "\n=== Running {$this->testName} ===\n";
        
        $methods = get_class_methods($this);
        foreach ($methods as $method) {
            if (strpos($method, 'test') === 0) {
                echo "\n-- {$method} --\n";
                $this->$method();
            }
        }

        echo "\n=== Results ===\n";
        echo "Total assertions: {$this->assertions}\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";
        echo ($this->failed === 0 ? "✓ ALL TESTS PASSED" : "✗ SOME TESTS FAILED") . "\n";
        
        return $this->failed === 0;
    }
}

/**
 * Mock classes for XenForo dependencies
 */
class MockApp extends \XF\App
{
    protected $options = [];
    protected $db;
    protected $router;

    public function __construct()
    {
        $this->db = new MockDb();
        $this->router = new MockRouter();
    }

    public function options($key = null)
    {
        if ($key === null) {
            return new MockOptions($this->options);
        }
        return $this->options[$key] ?? null;
    }

    public function setOption($key, $value)
    {
        $this->options[$key] = $value;
    }

    public function db()
    {
        return $this->db;
    }

    public function router($type = 'public')
    {
        return $this->router;
    }

    public function service($serviceClass)
    {
        // Return mock services based on class name
        if (strpos($serviceClass, 'Cache') !== false) {
            return new MockCacheService($this);
        }
        return new MockService($this);
    }

    public function repository($repoClass)
    {
        return new MockRepository($this);
    }

    public function em()
    {
        return new MockEntityManager($this);
    }

    public function request()
    {
        return new MockRequest();
    }

    public function config($key = null)
    {
        $config = [
            'cache' => [
                'redis' => [
                    'host' => 'localhost',
                    'port' => 6379,
                    'password' => ''
                ]
            ]
        ];
        return $key ? ($config[$key] ?? null) : $config;
    }
}

class MockOptions
{
    protected $options;

    public function __construct($options = [])
    {
        $this->options = array_merge([
            'hardmob_afiliados_cache_ttl' => 3600,
            'hardmob_afiliados_cache_driver' => 'file',
            'hardmob_afiliados_ga_tracking_id' => ''
        ], $options);
    }

    public function hardmob_afiliados_cache_ttl()
    {
        return $this->options['hardmob_afiliados_cache_ttl'];
    }

    public function hardmob_afiliados_cache_driver()
    {
        return $this->options['hardmob_afiliados_cache_driver'];
    }

    public function hardmob_afiliados_ga_tracking_id()
    {
        return $this->options['hardmob_afiliados_ga_tracking_id'];
    }

    public function __get($name)
    {
        return $this->options[$name] ?? null;
    }

    public function __call($name, $args)
    {
        return $this->options[$name] ?? null;
    }
}

class MockDb
{
    protected $data = [];

    public function fetchRow($query, $params = [])
    {
        // Simulate database responses
        if (strpos($query, 'xf_hardmob_affiliate_cache') !== false && strpos($query, 'COUNT') !== false) {
            return [
                'total' => 5,
                'expired' => 1,
                'active' => 4
            ];
        }
        if (strpos($query, 'xf_hardmob_affiliate_cache') !== false) {
            return [
                'cache_value' => '{"store_id":1,"affiliate_url":"https://amazon.com.br/dp/TEST?tag=test"}',
                'expiry_date' => time() + 3600
            ];
        }
        return null;
    }

    public function fetchOne($query, $params = [])
    {
        if (strpos($query, 'COUNT') !== false) {
            return 10; // Mock count
        }
        return null;
    }

    public function insert($table, $data, $onDuplicate = false, $duplicateAction = '')
    {
        $this->data[$table][] = $data;
        return true;
    }

    public function delete($table, $condition = '', $params = [])
    {
        return 1; // Mock affected rows
    }

    public function emptyTable($table)
    {
        unset($this->data[$table]);
        return true;
    }
}

class MockRouter
{
    public function buildLink($type, $params = [])
    {
        return '/affiliate/' . ($params['store_id'] ?? '1') . '/' . ($params['slug'] ?? 'test');
    }
}

class MockService
{
    protected $app;

    public function __construct($app)
    {
        $this->app = $app;
    }
}

class MockCacheService extends MockService
{
    protected $cache = [];

    public function get($key)
    {
        return $this->cache[$key] ?? null;
    }

    public function set($key, $value, $ttl = 0)
    {
        $this->cache[$key] = $value;
        return true;
    }
}

class MockRepository
{
    protected $app;

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function getStoreByDomain($domain)
    {
        return new MockStore(['domain' => $domain, 'store_id' => 1]);
    }

    public function findActiveStores()
    {
        return $this;
    }

    public function fetchOne()
    {
        return new MockStore(['store_id' => 1, 'name' => 'Test Store']);
    }

    public function fetch()
    {
        return [new MockStore(['store_id' => 1, 'name' => 'Test Store'])];
    }

    public function find($entityClass, $id)
    {
        return new MockStore(['store_id' => $id, 'name' => 'Test Store']);
    }
}

class MockEntityManager
{
    protected $app;

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function create($entityClass)
    {
        return new MockEntity();
    }
}

class MockEntity
{
    protected $data = [];

    public function __set($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function __get($key)
    {
        return $this->data[$key] ?? null;
    }

    public function save()
    {
        return true;
    }
}

class MockStore extends \hardMOB\Afiliados\Entity\Store
{
    public function __construct($data = [])
    {
        $this->data = array_merge([
            'store_id' => 1,
            'name' => 'Test Store',
            'domain' => 'example.com',
            'affiliate_code' => 'testcode'
        ], $data);
    }

    public function getConnectorClass()
    {
        return new MockConnector($this);
    }
}

class MockConnector
{
    protected $store;

    public function __construct($store)
    {
        $this->store = $store;
    }

    public function validateSlug($slug)
    {
        return !empty($slug);
    }

    public function generateAffiliateUrl($slug)
    {
        return 'https://' . $this->store->domain . '/' . $slug . '?tag=' . $this->store->affiliate_code;
    }
}

class MockRequest
{
    public function getIp()
    {
        return '127.0.0.1';
    }

    public function getServer($key, $default = '')
    {
        return $default;
    }
}

// Global XF class mock
class XF
{
    public static $time;

    public static function init()
    {
        self::$time = time();
    }
}

XF::init();

} // End of global namespace