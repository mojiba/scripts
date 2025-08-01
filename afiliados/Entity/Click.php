<?php

namespace hardMOB\Afiliados\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class Click extends Entity
{
    public function canView()
    {
        return \XF::visitor()->hasAdminPermission('hardmob_afiliados');
    }

    protected function _preSave()
    {
        if ($this->isInsert()) {
            $this->click_date = \XF::$time;
            
            $request = \XF::app()->request();
            $this->ip_address = $request->getIp(true);
            $this->user_agent = substr($request->getServer('HTTP_USER_AGENT', ''), 0, 500);
            $this->referrer = substr($request->getServer('HTTP_REFERER', ''), 0, 500);
        }
    }

    public static function getStructure(Structure $structure)
    {
        $structure->table = 'xf_hardmob_affiliate_clicks';
        $structure->shortName = 'hardMOB\Afiliados:Click';
        $structure->primaryKey = 'click_id';
        
        $structure->columns = [
            'click_id' => ['type' => self::UINT, 'autoIncrement' => true],
            'store_id' => ['type' => self::UINT, 'required' => true],
            'slug' => ['type' => self::STR, 'maxLength' => 500, 'required' => true],
            'user_id' => ['type' => self::UINT, 'default' => 0],
            'ip_address' => ['type' => self::BINARY, 'maxLength' => 16],
            'user_agent' => ['type' => self::STR, 'maxLength' => 500],
            'referrer' => ['type' => self::STR, 'maxLength' => 500],
            'click_date' => ['type' => self::UINT, 'required' => true]
        ];

        $structure->getters = [];
        
        $structure->relations = [
            'Store' => [
                'entity' => 'hardMOB\Afiliados:Store',
                'type' => self::TO_ONE,
                'conditions' => 'store_id',
                'primary' => true
            ],
            'User' => [
                'entity' => 'XF:User',
                'type' => self::TO_ONE,
                'conditions' => 'user_id',
                'primary' => true
            ]
        ];

        return $structure;
    }
}
