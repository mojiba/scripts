<?php

namespace hardMOB\AfiliateLinks;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\AddOn\StepRunnerUninstallTrait;

class Setup extends AbstractSetup
{
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;

    public function installStep1()
    {
        // Apenas criação de tabelas, se necessário.
        $this->schemaManager()->createTable('xf_hmaf_cache', function($table)
        {
            $table->addColumn('cache_id',   'varbinary', 32);
            $table->addColumn('cache_data', 'mediumblob');
            $table->addColumn('cache_date', 'int');
            $table->addPrimaryKey('cache_id');
        });

        // Pasta de cache (AliExpress)
        $cacheDir = \XF::getRootDirectory() . '/data/ae_cache';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }
    }

    public function uninstallStep1()
    {
        $this->schemaManager()->dropTable('xf_hmaf_cache');

        $cacheDir = \XF::getRootDirectory() . '/data/ae_cache';
        if (is_dir($cacheDir)) {
            foreach (glob($cacheDir . '/*.cache') as $file) {
                @unlink($file);
            }
            @rmdir($cacheDir);
        }
    }
	public function upgradeStep1()
{
    $this->applyDefaultData();
}
public function installStep2()
{
    $this->applyDefaultData();
}

}