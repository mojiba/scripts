<?php

namespace hardMOB\Afiliados\Service;

use XF\Service\AbstractService;
use hardMOB\Afiliados\Helper\Security;
use hardMOB\Afiliados\Entity\AuditLog;

class Configuration extends AbstractService
{
    protected $cache;
    protected $cacheKey = 'hardmob_affiliate_config';
    protected $cacheTtl = 3600; // 1 hour

    public function __construct(\XF\App $app)
    {
        parent::__construct($app);
        $this->cache = $app->cache();
    }

    /**
     * Get configuration value with caching
     */
    public function get($key, $default = null)
    {
        $config = $this->getAllConfig();
        return $config[$key] ?? $default;
    }

    /**
     * Set configuration value
     */
    public function set($key, $value)
    {
        $options = $this->app->options();
        $oldValue = $options->offsetGet("hardmob_afiliados_{$key}");
        
        // Security validation for certain keys
        if (!$this->validateConfigValue($key, $value)) {
            throw new \InvalidArgumentException("Invalid value for configuration key: {$key}");
        }

        // Update option
        $options->offsetSet("hardmob_afiliados_{$key}", $value);
        
        // Clear cache
        $this->clearCache();
        
        // Log configuration change
        AuditLog::logEvent(
            AuditLog::EVENT_UPDATE,
            'Configuration',
            0,
            "Configuration updated: {$key}",
            [$key => $oldValue],
            [$key => $value]
        );

        return true;
    }

    /**
     * Get all configuration with caching
     */
    protected function getAllConfig()
    {
        if ($this->cache) {
            $config = $this->cache->fetch($this->cacheKey);
            if ($config !== false) {
                return $config;
            }
        }

        $config = $this->loadConfigFromOptions();
        
        if ($this->cache) {
            $this->cache->save($this->cacheKey, $config, $this->cacheTtl);
        }

        return $config;
    }

    /**
     * Load configuration from XF options
     */
    protected function loadConfigFromOptions()
    {
        $options = $this->app->options();
        $config = [];

        // Security settings
        $config['allowed_domains'] = $options->hardmob_afiliados_allowed_domains ?? '';
        $config['enable_rate_limiting'] = $options->hardmob_afiliados_enable_rate_limiting ?? true;
        $config['rate_limit_attempts'] = $options->hardmob_afiliados_rate_limit_attempts ?? 100;
        $config['rate_limit_window'] = $options->hardmob_afiliados_rate_limit_window ?? 3600;
        
        // Cache settings
        $config['cache_driver'] = $options->hardmob_afiliados_cache_driver ?? 'file';
        $config['cache_ttl'] = $options->hardmob_afiliados_cache_ttl ?? 3600;
        
        // Analytics settings
        $config['ga_tracking_id'] = $options->hardmob_afiliados_ga_tracking_id ?? '';
        $config['enable_analytics'] = $options->hardmob_afiliados_enable_analytics ?? true;
        
        // Logging settings
        $config['log_retention'] = $options->hardmob_afiliados_log_retention ?? 90;
        $config['enable_security_logging'] = $options->hardmob_afiliados_enable_security_logging ?? true;
        
        // Performance settings
        $config['enable_pregeneration'] = $options->hardmob_afiliados_enable_pregeneration ?? false;
        $config['max_links_per_page'] = $options->hardmob_afiliados_max_links_per_page ?? 50;
        
        // Backup settings
        $config['enable_auto_backup'] = $options->hardmob_afiliados_enable_auto_backup ?? false;
        $config['backup_frequency'] = $options->hardmob_afiliados_backup_frequency ?? 'weekly';

        return $config;
    }

    /**
     * Validate configuration values
     */
    protected function validateConfigValue($key, $value)
    {
        switch ($key) {
            case 'allowed_domains':
                if (!empty($value)) {
                    $domains = array_filter(array_map('trim', explode("\n", $value)));
                    foreach ($domains as $domain) {
                        if (!Security::validateDomain($domain)) {
                            return false;
                        }
                    }
                }
                return true;

            case 'rate_limit_attempts':
                return is_numeric($value) && $value > 0 && $value <= 1000;

            case 'rate_limit_window':
                return is_numeric($value) && $value >= 60 && $value <= 86400;

            case 'cache_ttl':
                return is_numeric($value) && $value >= 60 && $value <= 86400;

            case 'ga_tracking_id':
                return empty($value) || preg_match('/^UA-\d+-\d+$|^G-[A-Z0-9]+$/', $value);

            case 'log_retention':
                return is_numeric($value) && $value >= 1 && $value <= 365;

            case 'max_links_per_page':
                return is_numeric($value) && $value > 0 && $value <= 500;

            case 'cache_driver':
                return in_array($value, ['file', 'redis', 'memcached']);

            case 'backup_frequency':
                return in_array($value, ['daily', 'weekly', 'monthly']);

            default:
                return true;
        }
    }

    /**
     * Clear configuration cache
     */
    public function clearCache()
    {
        if ($this->cache) {
            $this->cache->delete($this->cacheKey);
        }
    }

    /**
     * Get security configuration
     */
    public function getSecurityConfig()
    {
        return [
            'allowed_domains' => $this->get('allowed_domains', ''),
            'enable_rate_limiting' => $this->get('enable_rate_limiting', true),
            'rate_limit_attempts' => $this->get('rate_limit_attempts', 100),
            'rate_limit_window' => $this->get('rate_limit_window', 3600),
            'enable_security_logging' => $this->get('enable_security_logging', true)
        ];
    }

    /**
     * Get performance configuration
     */
    public function getPerformanceConfig()
    {
        return [
            'cache_driver' => $this->get('cache_driver', 'file'),
            'cache_ttl' => $this->get('cache_ttl', 3600),
            'enable_pregeneration' => $this->get('enable_pregeneration', false),
            'max_links_per_page' => $this->get('max_links_per_page', 50)
        ];
    }

    /**
     * Get analytics configuration
     */
    public function getAnalyticsConfig()
    {
        return [
            'ga_tracking_id' => $this->get('ga_tracking_id', ''),
            'enable_analytics' => $this->get('enable_analytics', true)
        ];
    }

    /**
     * Validate entire configuration
     */
    public function validateConfiguration(array $config)
    {
        $errors = [];

        foreach ($config as $key => $value) {
            if (!$this->validateConfigValue($key, $value)) {
                $errors[] = "Invalid value for {$key}";
            }
        }

        return $errors;
    }

    /**
     * Backup current configuration
     */
    public function backupConfiguration()
    {
        $config = $this->getAllConfig();
        $backup = [
            'timestamp' => \XF::$time,
            'version' => $this->app->addOnManager()->getById('hardMOB/Afiliados')->version_string,
            'config' => $config
        ];

        $backupDir = \XF::getRootDirectory() . '/internal_data/affiliate_backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $filename = $backupDir . '/config_backup_' . date('Y-m-d_H-i-s') . '.json';
        file_put_contents($filename, json_encode($backup, JSON_PRETTY_PRINT));

        // Log backup creation
        AuditLog::logEvent(
            AuditLog::EVENT_CREATE,
            'ConfigBackup',
            0,
            'Configuration backup created: ' . basename($filename)
        );

        return $filename;
    }

    /**
     * Restore configuration from backup
     */
    public function restoreConfiguration($backupFile)
    {
        if (!file_exists($backupFile)) {
            throw new \InvalidArgumentException('Backup file not found');
        }

        $backup = json_decode(file_get_contents($backupFile), true);
        if (!$backup || !isset($backup['config'])) {
            throw new \InvalidArgumentException('Invalid backup file format');
        }

        // Validate configuration before restore
        $errors = $this->validateConfiguration($backup['config']);
        if (!empty($errors)) {
            throw new \InvalidArgumentException('Invalid configuration in backup: ' . implode(', ', $errors));
        }

        // Apply configuration
        foreach ($backup['config'] as $key => $value) {
            $this->set($key, $value);
        }

        // Log restore
        AuditLog::logEvent(
            AuditLog::EVENT_UPDATE,
            'ConfigRestore',
            0,
            'Configuration restored from: ' . basename($backupFile)
        );

        return true;
    }
}