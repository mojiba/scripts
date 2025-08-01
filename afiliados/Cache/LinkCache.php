<?php

namespace hardMOB\Afiliados\Cache;

class LinkCache
{
    protected $app;
    protected $driver;

    public function __construct(\XF\App $app)
    {
        $this->app = $app;
        $this->driver = $app->options()->hardmob_afiliados_cache_driver ?: 'file';
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
    }

    protected function setToFile($key, $value, $ttl = 0)
    {
        $expiryDate = $ttl ? (\XF::$time + $ttl) : 0;
        
        $this->app->db()->insert('xf_hardmob_affiliate_cache', [
            'cache_key' => $key,
            'cache_value' => json_encode($value),
            'expiry_date' => $expiryDate,
            'created_date' => \XF::$time
        ], false, 'cache_value = VALUES(cache_value), expiry_date = VALUES(expiry_date)');

        return true;
    }

    protected function deleteFromFile($key)
    {
        return $this->app->db()->delete('xf_hardmob_affiliate_cache', 'cache_key = ?', $key);
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
            throw new \Exception('Redis configuration not found');
        }

        $redis = new \Redis();
        $redis->connect($config['redis']['host'], $config['redis']['port']);
        
        if (!empty($config['redis']['password'])) {
            $redis->auth($config['redis']['password']);
        }

        return $redis;
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
