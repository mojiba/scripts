<?php

namespace hardMOB\Afiliados\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

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
        if ($this->isChanged('name')) {
            $this->name = trim($this->name);
            if ($this->name === '') {
                $this->error(\XF::phrase('please_enter_valid_name'), 'name');
            }
        }

        if ($this->isChanged('domain')) {
            $this->domain = trim($this->domain);
            $this->domain = preg_replace('#^https?://#i', '', $this->domain);
            $this->domain = rtrim($this->domain, '/');
            
            if (!$this->domain) {
                $this->error(\XF::phrase('please_enter_valid_domain'), 'domain');
            }
        }

        if ($this->isChanged('affiliate_code')) {
            $this->affiliate_code = trim($this->affiliate_code);
            if ($this->affiliate_code === '') {
                $this->error(\XF::phrase('hardmob_afiliados_please_enter_affiliate_code'), 'affiliate_code');
            }
        }
    }

    protected function _postSave()
    {
        if ($this->isInsert()) {
            $this->created_date = \XF::$time;
        }
        
        if ($this->isChanged()) {
            $this->modified_date = \XF::$time;
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
