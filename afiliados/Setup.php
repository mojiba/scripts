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
        
        // Remove all main tables created by this add-on
        $tables = [
            'xf_hardmob_affiliate_stores',
            'xf_hardmob_affiliate_clicks', 
            'xf_hardmob_affiliate_cache'
        ];
        
        foreach ($tables as $table)
        {
            if ($sm->tableExists($table))
            {
                $sm->dropTable($table);
            }
        }
        
        // Clean up any potential conflict tables
        $conflictTables = [
            'xf_hardmob_affiliate_stores__conflict',
            'xf_hardmob_affiliate_clicks__conflict',
            'xf_hardmob_affiliate_cache__conflict'
        ];
        
        foreach ($conflictTables as $conflictTable)
        {
            if ($sm->tableExists($conflictTable))
            {
                $sm->dropTable($conflictTable);
            }
        }
    }

    public function uninstallStep2()
    {
        // All tables are now removed in uninstallStep1 for a more robust uninstall process
        // This method is kept for backward compatibility but performs no operations
    }

    public function uninstallStep3()
    {
        // All tables are now removed in uninstallStep1 for a more robust uninstall process
        // This method is kept for backward compatibility but performs no operations
    }
    
    public function preInstall()
    {
        // Clean up any existing conflict tables before installation
        $sm = $this->schemaManager();
        $conflictTables = [
            'xf_hardmob_affiliate_stores__conflict',
            'xf_hardmob_affiliate_clicks__conflict', 
            'xf_hardmob_affiliate_cache__conflict'
        ];
        
        foreach ($conflictTables as $table)
        {
            if ($sm->tableExists($table))
            {
                $sm->dropTable($table);
            }
        }
    }
}
