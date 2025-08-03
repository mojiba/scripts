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
        // Criação do grupo de opções e opções
        $this->createOptionGroup();
        $this->createAdminNavigation();
    }

    // Método de atualização para a versão 1.0.8
    public function upgrade1000800Step1()
    {
        // Cria o grupo de opções caso ainda não exista
        $this->createOptionGroup();
    }

    public function upgrade1000800Step2()
    {
        // Cria a navegação de admin caso ainda não exista
        $this->createAdminNavigation();
    }

    protected function createOptionGroup()
    {
        $optionGroup = \XF::finder('XF:OptionGroup')
            ->where('group_id', '=', 'hardMOB_afiliados')
            ->fetchOne();
            
        if (!$optionGroup)
        {
            $this->db()->insert('xf_option_group', [
                'group_id' => 'hardMOB_afiliados',
                'display_order' => 1000,
                'debug_only' => 0
            ]);
            
            // Adiciona frases para o grupo de opções
            $this->insertMasterPhrase('option_group_hardMOB_afiliados', 'Sistema de Afiliados');
            $this->insertMasterPhrase('option_group_hardMOB_afiliados_description', 'Configure as opções do sistema de afiliados');
            
            // Adiciona opção para ativar/desativar o sistema
            $this->db()->insert('xf_option', [
                'option_id' => 'hardMOB_afiliados_enabled',
                'option_value' => 1,
                'default_value' => 1,
                'edit_format' => 'onoff',
                'edit_format_params' => '',
                'data_type' => 'boolean',
                'sub_options' => '',
                'validation_class' => '',
                'validation_method' => '',
                'advanced' => 0,
                'addon_id' => 'hardMOB/Afiliados'
            ]);
            
            $this->db()->insert('xf_option_group_relation', [
                'option_id' => 'hardMOB_afiliados_enabled',
                'group_id' => 'hardMOB_afiliados',
                'display_order' => 10
            ]);
            
            $this->insertMasterPhrase('option_hardMOB_afiliados_enabled', 'Ativar Sistema de Afiliados');
            $this->insertMasterPhrase('option_hardMOB_afiliados_enabled_explain', 'Habilita ou desabilita todo o sistema de afiliados');
            
            // Adiciona opção para domínios de afiliados
            $defaultDomains = json_encode(['amazon.com.br', 'magazineluiza.com.br', 'kabum.com.br']);
            
            $this->db()->insert('xf_option', [
                'option_id' => 'hardMOB_afiliados_domains',
                'option_value' => $defaultDomains,
                'default_value' => $defaultDomains,
                'edit_format' => 'textarea',
                'edit_format_params' => json_encode(['rows' => 6]),
                'data_type' => 'array',
                'sub_options' => '',
                'validation_class' => '',
                'validation_method' => '',
                'advanced' => 0,
                'addon_id' => 'hardMOB/Afiliados'
            ]);
            
            $this->db()->insert('xf_option_group_relation', [
                'option_id' => 'hardMOB_afiliados_domains',
                'group_id' => 'hardMOB_afiliados',
                'display_order' => 20
            ]);
            
            $this->insertMasterPhrase('option_hardMOB_afiliados_domains', 'Domínios de Afiliados');
            $this->insertMasterPhrase('option_hardMOB_afiliados_domains_explain', 'Lista de domínios que serão convertidos em links de afiliados (um por linha)');
        }
    }

    protected function createAdminNavigation()
    {
        $adminNavigation = \XF::finder('XF:AdminNavigation')
            ->where('navigation_id', '=', 'hardMOB_afiliados')
            ->fetchOne();
            
        if (!$adminNavigation)
        {
            $this->db()->insert('xf_admin_navigation', [
                'navigation_id' => 'hardMOB_afiliados',
                'parent_navigation_id' => 'setup',
                'display_order' => 1000,
                'link' => 'options/groups/hardMOB_afiliados',
                'icon' => 'fa-link',
                'admin_permission_id' => 'option',
                'debug_only' => 0,
                'hide_no_children' => 0
            ]);
            
            $this->insertMasterPhrase('admin_navigation.hardMOB_afiliados', 'Sistema de Afiliados');
        }
    }

    /**
     * Método auxiliar para inserir frases master
     */
    protected function insertMasterPhrase($title, $phraseText, $addOnId = null)
    {
        $addOnId = $addOnId ?: $this->addOn->getAddOnId();
        
        $phrase = \XF::finder('XF:Phrase')
            ->where('title', '=', $title)
            ->where('language_id', '=', 0)
            ->fetchOne();
            
        if (!$phrase)
        {
            $phrase = \XF::em()->create('XF:Phrase');
            $phrase->title = $title;
            $phrase->language_id = 0;
            $phrase->addon_id = $addOnId;
        }
        
        $phrase->phrase_text = $phraseText;
        $phrase->save();
        
        return $phrase;
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
        // Remove opções e grupo de opções
        $this->db()->delete('xf_option', "option_id LIKE 'hardMOB_afiliados_%'");
        $this->db()->delete('xf_option_group_relation', "group_id = 'hardMOB_afiliados'");
        $this->db()->delete('xf_option_group', "group_id = 'hardMOB_afiliados'");
        
        // Remove navegação admin
        $this->db()->delete('xf_admin_navigation', "navigation_id = 'hardMOB_afiliados'");
        
        // Remove as frases
        $this->db()->delete('xf_phrase', "title LIKE 'option_group_hardMOB_afiliados%' OR title LIKE 'option_hardMOB_afiliados%' OR title = 'admin_navigation.hardMOB_afiliados'");
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