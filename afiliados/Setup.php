<?php

namespace hardMOB\Afiliados;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Alter;
use XF\Db\Schema\Create;

class Setup extends AbstractSetup
{
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;

    public function installStep1()
    {
        $this->createStoresTable();
    }

    public function installStep2()
    {
        $this->createClicksTable();
    }

    public function installStep3()
    {
        $this->createCacheTable();
    }

    public function installStep4()
    {
        $this->createAuditLogTable();
    }

    protected function createAuditLogTable()
    {
        $sm = $this->schemaManager();
        
        if (!$sm->tableExists('xf_hardmob_affiliate_audit_logs'))
        {
            $sm->createTable('xf_hardmob_affiliate_audit_logs', function(Create $table)
            {
                $table->addColumn('log_id', 'int')->autoIncrement();
                $table->addColumn('event_type', 'varchar', 50);
                $table->addColumn('entity_type', 'varchar', 100);
                $table->addColumn('entity_id', 'int')->setDefault(0);
                $table->addColumn('user_id', 'int')->setDefault(0);
                $table->addColumn('ip_address', 'varbinary', 16);
                $table->addColumn('user_agent', 'varchar', 500);
                $table->addColumn('old_data', 'mediumtext');
                $table->addColumn('new_data', 'mediumtext');
                $table->addColumn('description', 'varchar', 500);
                $table->addColumn('created_date', 'int');
                $table->addPrimaryKey('log_id');
                $table->addKey(['event_type'], 'event_type');
                $table->addKey(['user_id'], 'user_id');
                $table->addKey(['created_date'], 'created_date');
                $table->addKey(['entity_type', 'entity_id'], 'entity');
            });
        }
    }

    protected function createStoresTable()
    {
        $sm = $this->schemaManager();
        
        if (!$sm->tableExists('xf_hardmob_affiliate_stores'))
        {
            $sm->createTable('xf_hardmob_affiliate_stores', function(Create $table)
            {
                $table->addColumn('store_id', 'int')->autoIncrement();
                $table->addColumn('name', 'varchar', 100);
                $table->addColumn('domain', 'varchar', 255);
                $table->addColumn('affiliate_code', 'varchar', 100);
                $table->addColumn('status', 'enum')->values(['active', 'inactive'])->setDefault('active');
                $table->addColumn('created_date', 'int')->setDefault(0);
                $table->addColumn('modified_date', 'int')->setDefault(0);
                $table->addPrimaryKey('store_id');
                $table->addUniqueKey(['name'], 'name');
                $table->addKey(['status'], 'status');
            });
        }
    }

    protected function createClicksTable()
    {
        $sm = $this->schemaManager();
        
        if (!$sm->tableExists('xf_hardmob_affiliate_clicks'))
        {
            $sm->createTable('xf_hardmob_affiliate_clicks', function(Create $table)
            {
                $table->addColumn('click_id', 'int')->autoIncrement();
                $table->addColumn('store_id', 'int');
                $table->addColumn('slug', 'varchar', 500);
                $table->addColumn('user_id', 'int')->setDefault(0);
                $table->addColumn('ip_address', 'varbinary', 16);
                $table->addColumn('user_agent', 'text');
                $table->addColumn('referrer', 'text');
                $table->addColumn('click_date', 'int');
                $table->addPrimaryKey('click_id');
                $table->addKey(['store_id'], 'store_id');
                $table->addKey(['user_id'], 'user_id');
                $table->addKey(['click_date'], 'click_date');
            });
        }
    }

    protected function createCacheTable()
    {
        $sm = $this->schemaManager();
        
        if (!$sm->tableExists('xf_hardmob_affiliate_cache'))
        {
            $sm->createTable('xf_hardmob_affiliate_cache', function(Create $table)
            {
                $table->addColumn('cache_key', 'varchar', 255);
                $table->addColumn('cache_value', 'mediumtext');
                $table->addColumn('expiry_date', 'int')->setDefault(0);
                $table->addColumn('created_date', 'int');
                $table->addPrimaryKey('cache_key');
                $table->addKey(['expiry_date'], 'expiry_date');
            });
        }
    }

    public function uninstallStep1()
    {
        $sm = $this->schemaManager();
        if ($sm->tableExists('xf_hardmob_affiliate_stores'))
        {
            $sm->dropTable('xf_hardmob_affiliate_stores');
        }
    }

    public function uninstallStep2()
    {
        $sm = $this->schemaManager();
        if ($sm->tableExists('xf_hardmob_affiliate_clicks'))
        {
            $sm->dropTable('xf_hardmob_affiliate_clicks');
        }
        
        // Clean up any conflict tables
        if ($sm->tableExists('xf_hardmob_affiliate_clicks__conflict'))
        {
            $sm->dropTable('xf_hardmob_affiliate_clicks__conflict');
        }
    }

    public function uninstallStep3()
    {
        $sm = $this->schemaManager();
        if ($sm->tableExists('xf_hardmob_affiliate_cache'))
        {
            $sm->dropTable('xf_hardmob_affiliate_cache');
        }
    }

    public function uninstallStep4()
    {
        $sm = $this->schemaManager();
        if ($sm->tableExists('xf_hardmob_affiliate_audit_logs'))
        {
            $sm->dropTable('xf_hardmob_affiliate_audit_logs');
        }
    }
    
    public function preInstall()
    {
        // Clean up any existing conflict tables before installation
        $sm = $this->schemaManager();
        $conflictTables = [
            'xf_hardmob_affiliate_stores__conflict',
            'xf_hardmob_affiliate_clicks__conflict', 
            'xf_hardmob_affiliate_cache__conflict',
            'xf_hardmob_affiliate_audit_logs__conflict'
        ];
        
        foreach ($conflictTables as $table)
        {
            if ($sm->tableExists($table))
            {
                $sm->dropTable($table);
            }
        }
    }

    public function upgrade1001201Step1()
    {
        // Upgrade to add audit logging
        $this->createAuditLogTable();
    }

    public function upgrade1001201Step2()
    {
        // Add indexes for better performance
        $sm = $this->schemaManager();
        
        if ($sm->tableExists('xf_hardmob_affiliate_stores'))
        {
            $sm->alterTable('xf_hardmob_affiliate_stores', function(Alter $table)
            {
                if (!$table->indexExists('domain'))
                {
                    $table->addKey(['domain'], 'domain');
                }
            });
        }
        
        if ($sm->tableExists('xf_hardmob_affiliate_clicks'))
        {
            $sm->alterTable('xf_hardmob_affiliate_clicks', function(Alter $table)
            {
                if (!$table->indexExists('click_date_store'))
                {
                    $table->addKey(['click_date', 'store_id'], 'click_date_store');
                }
            });
        }
    }
}