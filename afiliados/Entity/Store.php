<?php

namespace hardMOB\Afiliados\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;
use hardMOB\Afiliados\Helper\Security;

class Store extends Entity
{
    public function canEdit()
    {
        return \XF::visitor()->hasAdminPermission('hardmob_afiliados');
    }

    public function canDelete()
    {
        return \XF::visitor()->hasAdminPermission('hardmob_afiliados');
    }

    protected function _preSave()
    {
        // Store old data for audit logging
        if ($this->isUpdate()) {
            $this->setOption('audit_old_data', $this->getExistingValue());
        }

        if ($this->isChanged('name')) {
            $this->name = Security::sanitizeInput($this->name);
            if ($this->name === '') {
                $this->error(\XF::phrase('please_enter_valid_name'), 'name');
            }
            
            // Check for duplicate names
            $existing = $this->em()->findOne('hardMOB\Afiliados:Store', [
                'name' => $this->name,
                'store_id' => ['!=', $this->store_id ?: 0]
            ]);
            
            if ($existing) {
                $this->error(\XF::phrase('hardmob_afiliados_store_name_already_exists'), 'name');
            }
        }

        if ($this->isChanged('domain')) {
            $this->domain = Security::sanitizeInput($this->domain, 'domain');
            
            if (!Security::validateDomain($this->domain)) {
                $this->error(\XF::phrase('hardmob_afiliados_please_enter_valid_domain'), 'domain');
            }

            // Check against allowed domains
            $allowedDomains = Security::getAllowedDomains();
            if (!empty($allowedDomains) && !Security::validateUrl('https://' . $this->domain, $allowedDomains)) {
                $this->error(\XF::phrase('hardmob_afiliados_domain_not_allowed'), 'domain');
            }
            
            // Check for duplicate domains
            $existing = $this->em()->findOne('hardMOB\Afiliados:Store', [
                'domain' => $this->domain,
                'store_id' => ['!=', $this->store_id ?: 0]
            ]);
            
            if ($existing) {
                $this->error(\XF::phrase('hardmob_afiliados_domain_already_exists'), 'domain');
            }
        }

        if ($this->isChanged('affiliate_code')) {
            $this->affiliate_code = Security::sanitizeInput($this->affiliate_code);
            
            if (!Security::validateAffiliateCode($this->affiliate_code)) {
                $this->error(\XF::phrase('hardmob_afiliados_invalid_affiliate_code'), 'affiliate_code');
            }
        }

        // Validate status
        if ($this->isChanged('status')) {
            if (!in_array($this->status, ['active', 'inactive'])) {
                $this->error(\XF::phrase('hardmob_afiliados_invalid_status'), 'status');
            }
        }
    }

    protected function _postSave()
    {
        if ($this->isInsert()) {
            $this->created_date = \XF::$time;
            
            // Log store creation
            AuditLog::logEvent(
                AuditLog::EVENT_CREATE,
                'Store',
                $this->store_id,
                'Store created: ' . $this->name
            );
        }
        
        if ($this->isChanged()) {
            $this->modified_date = \XF::$time;
            
            if ($this->isUpdate()) {
                // Log store update
                $oldData = $this->getOption('audit_old_data') ?: [];
                $newData = $this->toArray();
                
                AuditLog::logEvent(
                    AuditLog::EVENT_UPDATE,
                    'Store',
                    $this->store_id,
                    'Store updated: ' . $this->name,
                    $oldData,
                    $newData
                );
            }
        }
        
        // Clear cache when store is modified
        $this->clearStoreCache();
    }

    protected function _postDelete()
    {
        // Log store deletion
        AuditLog::logEvent(
            AuditLog::EVENT_DELETE,
            'Store',
            $this->store_id,
            'Store deleted: ' . $this->name
        );
        
        // Clear cache
        $this->clearStoreCache();
    }

    protected function clearStoreCache()
    {
        $cache = \XF::app()->cache();
        if ($cache) {
            $cache->delete('hardmob_affiliate_stores');
            $cache->delete('hardmob_affiliate_active_stores');
            $cache->delete('hardmob_affiliate_store_' . $this->store_id);
        }
    }

    public function getConnectorClass()
    {
        $connectorName = preg_replace('/[^a-zA-Z0-9]/', '', $this->name);
        $className = "hardMOB\\Afiliados\\Connector\\{$connectorName}";
        
        if (class_exists($className)) {
            return new $className($this);
        }
        
        return new \hardMOB\Afiliados\Connector\CustomStore($this);
    }

    /**
     * Check if store domain is allowed
     */
    public function isDomainAllowed()
    {
        $allowedDomains = Security::getAllowedDomains();
        return empty($allowedDomains) || Security::validateUrl('https://' . $this->domain, $allowedDomains);
    }

    /**
     * Get store statistics
     */
    public function getStats($period = 'month')
    {
        $analytics = \XF::app()->service('hardMOB\Afiliados:Analytics');
        return $analytics->getConversionRate($this->store_id, $period);
    }

    /**
     * Validate affiliate link format for this store
     */
    public function validateAffiliateLink($url)
    {
        if (!Security::validateUrl($url)) {
            return false;
        }

        $parsedUrl = parse_url($url);
        if (!$parsedUrl || empty($parsedUrl['host'])) {
            return false;
        }

        $urlDomain = strtolower($parsedUrl['host']);
        $storeDomain = strtolower($this->domain);

        // Check if URL belongs to this store's domain
        return $urlDomain === $storeDomain || str_ends_with($urlDomain, '.' . $storeDomain);
    }

    public static function getStructure(Structure $structure)
    {
        $structure->table = 'xf_hardmob_affiliate_stores';
        $structure->shortName = 'hardMOB\Afiliados:Store';
        $structure->primaryKey = 'store_id';
        
        $structure->columns = [
            'store_id' => ['type' => self::UINT, 'autoIncrement' => true],
            'name' => ['type' => self::STR, 'maxLength' => 100, 'required' => true],
            'domain' => ['type' => self::STR, 'maxLength' => 255, 'required' => true],
            'affiliate_code' => ['type' => self::STR, 'maxLength' => 100, 'required' => true],
            'status' => ['type' => self::STR, 'default' => 'active', 'allowedValues' => ['active', 'inactive']],
            'created_date' => ['type' => self::UINT, 'default' => 0],
            'modified_date' => ['type' => self::UINT, 'default' => 0]
        ];

        $structure->getters = [];
        $structure->relations = [];

        return $structure;
    }
}
