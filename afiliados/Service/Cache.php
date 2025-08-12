<?php

namespace hardMOB\Afiliados\Service;

use XF\Service\AbstractService;

/**
 * Unified cache service for affiliate system
 */
class Cache extends AbstractService
{
    protected $linkCache;
    protected $driver;

    public function __construct(\XF\App $app)
    {
        parent::__construct($app);
        $this->linkCache = new \hardMOB\Afiliados\Cache\LinkCache($app);
        $this->driver = $this->app->options()->hardmob_afiliados_cache_driver ?: 'file';
    }

    /**
     * Get cached value
     */
    public function get($key)
    {
        return $this->linkCache->get($key);
    }

    /**
     * Set cached value
     */
    public function set($key, $value, $ttl = 0)
    {
        return $this->linkCache->set($key, $value, $ttl);
    }

    /**
     * Delete cached value
     */
    public function delete($key)
    {
        return $this->linkCache->delete($key);
    }

    /**
     * Clear all cache
     */
    public function clearAll()
    {
        return $this->linkCache->clearAll();
    }

    /**
     * Get cache statistics
     */
    public function getStats()
    {
        return $this->linkCache->getStats();
    }

    /**
     * Clean expired entries
     */
    public function cleanExpired()
    {
        return $this->linkCache->cleanExpired();
    }

    /**
     * Warm up cache with popular links
     */
    public function warmUp()
    {
        $generator = $this->app->service('hardMOB\Afiliados:AffiliateGenerator');
        return $generator->preGenerateLinks();
    }

    /**
     * Get cache health status
     */
    public function getHealthStatus()
    {
        $stats = $this->getStats();
        $config = $this->app->service('hardMOB\Afiliados:Configuration');
        
        $health = [
            'status' => 'healthy',
            'driver' => $this->driver,
            'total_entries' => $stats['total_entries'],
            'active_entries' => $stats['active_entries'],
            'expired_entries' => $stats['expired_entries'],
            'hit_ratio' => $this->calculateHitRatio(),
            'memory_usage' => $this->getMemoryUsage(),
            'recommendations' => []
        ];

        // Analyze health
        if ($stats['expired_entries'] > $stats['active_entries'] * 0.3) {
            $health['status'] = 'warning';
            $health['recommendations'][] = 'High number of expired entries. Consider running cache cleanup.';
        }

        if ($health['hit_ratio'] < 0.6) {
            $health['status'] = 'warning';
            $health['recommendations'][] = 'Low cache hit ratio. Consider increasing cache TTL.';
        }

        if ($health['memory_usage'] > 85) {
            $health['status'] = 'critical';
            $health['recommendations'][] = 'High memory usage. Consider cache cleanup or increasing memory limits.';
        }

        return $health;
    }

    /**
     * Calculate cache hit ratio (simplified)
     */
    protected function calculateHitRatio()
    {
        // This would need to be implemented with actual hit/miss tracking
        // For now, return a calculated estimate based on active vs total
        $stats = $this->getStats();
        
        if ($stats['total_entries'] == 0) {
            return 0;
        }
        
        return round(($stats['active_entries'] / $stats['total_entries']) * 100, 2);
    }

    /**
     * Get memory usage percentage
     */
    protected function getMemoryUsage()
    {
        // Simplified memory usage calculation
        return round((memory_get_usage() / memory_get_peak_usage()) * 100, 2);
    }

    /**
     * Optimize cache performance
     */
    public function optimize()
    {
        $optimizations = [];
        
        // Clean expired entries
        $cleaned = $this->cleanExpired();
        if ($cleaned > 0) {
            $optimizations['expired_cleaned'] = $cleaned;
        }

        // Warm up cache if enabled
        $config = $this->app->service('hardMOB\Afiliados:Configuration');
        if ($config->get('enable_pregeneration', false)) {
            $warmed = $this->warmUp();
            if ($warmed > 0) {
                $optimizations['links_pregenerated'] = $warmed;
            }
        }

        return $optimizations;
    }

    /**
     * Export cache data for backup
     */
    public function exportData()
    {
        $stats = $this->getStats();
        $health = $this->getHealthStatus();
        
        return [
            'timestamp' => \XF::$time,
            'driver' => $this->driver,
            'stats' => $stats,
            'health' => $health
        ];
    }
}