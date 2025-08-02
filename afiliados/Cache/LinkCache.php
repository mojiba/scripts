<?php

namespace hardMOB\Afiliados\Cache;

class LinkCache
{
    protected $app;
    protected $driver;
    protected $errorHandler;

    public function __construct(\XF\App $app)
    {
        $this->app = $app;
        $this->driver = $app->options()->hardmob_afiliados_cache_driver ?: 'file';
        $this->errorHandler = $app->service('hardMOB\Afiliados:ErrorHandler');
    }

    public function get($key)
    {
        switch ($this->driver)
        {
            case 'redis':
                return $this->getFromRedis($key);
                
            case 'file':
            default:
                return $this->getFromFile($key);
        }
    }

    public function set($key, $value, $ttl = 0)
    {
        switch ($this->driver)
        {
            case 'redis':
                return $this->setToRedis($key, $value, $ttl);
                
            case 'file':
            default:
                return $this->setToFile($key, $value, $ttl);
        }
    }

    public function delete($key)
    {
        switch ($this->driver)
        {
            case 'redis':
                return $this->deleteFromRedis($key);
                
            case 'file':
            default:
                return $this->deleteFromFile($key);
        }
    }

    public function clearAll()
    {
        switch ($this->driver)
        {
            case 'redis':
                return $this->clearRedis();
                
            case 'file':
            default:
                return $this->clearFile();
        }
    }

    protected function getFromFile($key)
    {
        try {
            $record = $this->app->db()->fetchRow(
                'SELECT cache_value, expiry_date FROM xf_hardmob_affiliate_cache WHERE cache_key = ?',
                $key
            );

            if (!$record)
            {
                return null;
            }

            if ($record['expiry_date'] && $record['expiry_date'] < \XF::$time)
            {
                $this->deleteFromFile($key);
                return null;
            }

            return json_decode($record['cache_value'], true);
        } catch (\Exception $e) {
            $this->errorHandler->logError('cache_database', 'Database cache read failed', ['error' => $e->getMessage()], $e);
            return null;
        }
    }

    protected function setToFile($key, $value, $ttl = 0)
    {
        try {
            $expiryDate = $ttl ? (\XF::$time + $ttl) : 0;
            
            $this->app->db()->insert('xf_hardmob_affiliate_cache', [
                'cache_key' => $key,
                'cache_value' => json_encode($value),
                'expiry_date' => $expiryDate,
                'created_date' => \XF::$time
            ], false, 'cache_value = VALUES(cache_value), expiry_date = VALUES(expiry_date)');

            return true;
        } catch (\Exception $e) {
            $this->errorHandler->logError('cache_database', 'Database cache write failed', ['error' => $e->getMessage()], $e);
            return false;
        }
    }

    protected function deleteFromFile($key)
    {
        try {
            return $this->app->db()->delete('xf_hardmob_affiliate_cache', 'cache_key = ?', $key);
        } catch (\Exception $e) {
            $this->errorHandler->logError('cache_database', 'Database cache delete failed', ['error' => $e->getMessage()], $e);
            return false;
        }
    }

    protected function clearFile()
    {
        return $this->app->db()->emptyTable('xf_hardmob_affiliate_cache');
    }

    protected function getFromRedis($key)
    {
        try {
            $redis = $this->getRedisConnection();
            $value = $redis->get('hardmob_afiliados:' . $key);
            return $value ? json_decode($value, true) : null;
        } catch (\Exception $e) {
            $this->errorHandler->logError('cache_redis_operation', 'Redis get operation failed', ['error' => $e->getMessage()], $e);
            $this->errorHandler->logInfo('cache_fallback', 'Using database cache as fallback');
            return $this->getFromFile($key); // Fallback
        }
    }

    protected function setToRedis($key, $value, $ttl = 0)
    {
        try {
            $redis = $this->getRedisConnection();
            $redisKey = 'hardmob_afiliados:' . $key;
            
            if ($ttl > 0) {
                return $redis->setex($redisKey, $ttl, json_encode($value));
            } else {
                return $redis->set($redisKey, json_encode($value));
            }
        } catch (\Exception $e) {
            $this->errorHandler->logError('cache_redis_operation', 'Redis set operation failed', ['error' => $e->getMessage()], $e);
            $this->errorHandler->logInfo('cache_fallback', 'Using database cache as fallback');
            return $this->setToFile($key, $value, $ttl); // Fallback
        }
    }

    protected function deleteFromRedis($key)
    {
        try {
            $redis = $this->getRedisConnection();
            return $redis->del('hardmob_afiliados:' . $key);
        } catch (\Exception $e) {
            return $this->deleteFromFile($key); // Fallback
        }
    }

    protected function clearRedis()
    {
        try {
            $redis = $this->getRedisConnection();
            $keys = $redis->keys('hardmob_afiliados:*');
            return $keys ? $redis->del($keys) : 0;
        } catch (\Exception $e) {
            return $this->clearFile(); // Fallback
        }
    }

    protected function getRedisConnection()
    {
        $config = $this->app->config('cache');
        if (!isset($config['redis'])) {
            $this->errorHandler->logError('cache_redis_connection', 'Redis configuration not found');
            throw new \Exception('Redis configuration not found');
        }

        try {
            $redis = new \Redis();
            $connected = $redis->connect($config['redis']['host'], $config['redis']['port']);
            
            if (!$connected) {
                throw new \Exception('Could not connect to Redis server');
            }
            
            if (!empty($config['redis']['password'])) {
                $auth = $redis->auth($config['redis']['password']);
                if (!$auth) {
                    throw new \Exception('Redis authentication failed');
                }
            }

            return $redis;
        } catch (\Exception $e) {
            $this->errorHandler->logError('cache_redis_connection', 'Failed to connect to Redis', ['error' => $e->getMessage()], $e);
            throw $e;
        }
    }

    public function getStats()
    {
        $stats = [
            'total_entries' => 0,
            'expired_entries' => 0,
            'active_entries' => 0
        ];

        $result = $this->app->db()->fetchRow('
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN expiry_date > 0 AND expiry_date < ? THEN 1 ELSE 0 END) as expired,
                SUM(CASE WHEN expiry_date = 0 OR expiry_date >= ? THEN 1 ELSE 0 END) as active
            FROM xf_hardmob_affiliate_cache
        ', [\XF::$time, \XF::$time]);

        if ($result) {
            $stats['total_entries'] = $result['total'];
            $stats['expired_entries'] = $result['expired'];
            $stats['active_entries'] = $result['active'];
        }

        return $stats;
    }

    public function cleanExpired()
    {
        return $this->app->db()->delete(
            'xf_hardmob_affiliate_cache',
            'expiry_date > 0 AND expiry_date < ?',
            \XF::$time
        );
    }
}
