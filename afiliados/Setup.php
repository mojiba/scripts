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
        $this->createOptions();
    }

    // Método de atualização para a versão 1.0.10
    public function upgrade1001000Step1()
    {
        $this->createOptions();
    }

    protected function createOptions()
    {
        // Criar grupo de opções usando Entity API
        $optionGroup = \XF::em()->find('XF:OptionGroup', 'hardMOB_afiliados');
        
        if (!$optionGroup)
        {
            /** @var \XF\Entity\OptionGroup $optionGroup */
            $optionGroup = \XF::em()->create('XF:OptionGroup');
            $optionGroup->group_id = 'hardMOB_afiliados';
            $optionGroup->display_order = 1000;
            $optionGroup->debug_only = false;
            $optionGroup->save();
            
            // Criar frases para o grupo de opções
            /** @var \XF\Entity\Phrase $phrase */
            $phrase = \XF::em()->create('XF:Phrase');
            $phrase->title = 'option_group_hardMOB_afiliados';
            $phrase->language_id = 0;
            $phrase->addon_id = 'hardMOB/Afiliados';
            $phrase->phrase_text = 'Sistema de Afiliados';
            $phrase->save();
            
            /** @var \XF\Entity\Phrase $descPhrase */
            $descPhrase = \XF::em()->create('XF:Phrase');
            $descPhrase->title = 'option_group_hardMOB_afiliados_description';
            $descPhrase->language_id = 0;
            $descPhrase->addon_id = 'hardMOB/Afiliados';
            $descPhrase->phrase_text = 'Configure as opções do sistema de afiliados';
            $descPhrase->save();
        }
        
        // Criar opção para ativar/desativar
        $enabledOption = \XF::em()->find('XF:Option', 'hardMOB_afiliados_enabled');
        
        if (!$enabledOption)
        {
            /** @var \XF\Entity\Option $enabledOption */
            $enabledOption = \XF::em()->create('XF:Option');
            $enabledOption->option_id = 'hardMOB_afiliados_enabled';
            $enabledOption->option_value = 1;
            $enabledOption->default_value = 1;
            $enabledOption->edit_format = 'onoff';
            $enabledOption->data_type = 'boolean';
            $enabledOption->addon_id = 'hardMOB/Afiliados';
            $enabledOption->save();
            
            // Associar ao grupo
            /** @var \XF\Entity\OptionGroupRelation $relation */
            $relation = \XF::em()->create('XF:OptionGroupRelation');
            $relation->option_id = 'hardMOB_afiliados_enabled';
            $relation->group_id = 'hardMOB_afiliados';
            $relation->display_order = 10;
            $relation->save();
            
            // Criar frases para a opção
            /** @var \XF\Entity\Phrase $titlePhrase */
            $titlePhrase = \XF::em()->create('XF:Phrase');
            $titlePhrase->title = 'option_hardMOB_afiliados_enabled';
            $titlePhrase->language_id = 0;
            $titlePhrase->addon_id = 'hardMOB/Afiliados';
            $titlePhrase->phrase_text = 'Ativar Sistema de Afiliados';
            $titlePhrase->save();
            
            /** @var \XF\Entity\Phrase $explainPhrase */
            $explainPhrase = \XF::em()->create('XF:Phrase');
            $explainPhrase->title = 'option_hardMOB_afiliados_enabled_explain';
            $explainPhrase->language_id = 0;
            $explainPhrase->addon_id = 'hardMOB/Afiliados';
            $explainPhrase->phrase_text = 'Habilita ou desabilita todo o sistema de afiliados';
            $explainPhrase->save();
        }
        
        // Criar opção para domínios
        $domainsOption = \XF::em()->find('XF:Option', 'hardMOB_afiliados_domains');
        
        if (!$domainsOption)
        {
            /** @var \XF\Entity\Option $domainsOption */
            $domainsOption = \XF::em()->create('XF:Option');
            $domainsOption->option_id = 'hardMOB_afiliados_domains';
            $domainsOption->option_value = json_encode(['amazon.com.br', 'magazineluiza.com.br', 'kabum.com.br']);
            $domainsOption->default_value = json_encode(['amazon.com.br', 'magazineluiza.com.br', 'kabum.com.br']);
            $domainsOption->edit_format = 'textarea';
            $domainsOption->edit_format_params = json_encode(['rows' => 6]);
            $domainsOption->data_type = 'array';
            $domainsOption->addon_id = 'hardMOB/Afiliados';
            $domainsOption->save();
            
            // Associar ao grupo
            /** @var \XF\Entity\OptionGroupRelation $relation */
            $relation = \XF::em()->create('XF:OptionGroupRelation');
            $relation->option_id = 'hardMOB_afiliados_domains';
            $relation->group_id = 'hardMOB_afiliados';
            $relation->display_order = 20;
            $relation->save();
            
            // Criar frases para a opção
            /** @var \XF\Entity\Phrase $titlePhrase */
            $titlePhrase = \XF::em()->create('XF:Phrase');
            $titlePhrase->title = 'option_hardMOB_afiliados_domains';
            $titlePhrase->language_id = 0;
            $titlePhrase->addon_id = 'hardMOB/Afiliados';
            $titlePhrase->phrase_text = 'Domínios de Afiliados';
            $titlePhrase->save();
            
            /** @var \XF\Entity\Phrase $explainPhrase */
            $explainPhrase = \XF::em()->create('XF:Phrase');
            $explainPhrase->title = 'option_hardMOB_afiliados_domains_explain';
            $explainPhrase->language_id = 0;
            $explainPhrase->addon_id = 'hardMOB/Afiliados';
            $explainPhrase->phrase_text = 'Lista de domínios que serão convertidos em links de afiliados (um por linha)';
            $explainPhrase->save();
        }
        
        // Criar navegação de admin
        $adminNav = \XF::em()->find('XF:AdminNavigation', 'hardMOB_afiliados');
        
        if (!$adminNav)
        {
            /** @var \XF\Entity\AdminNavigation $adminNav */
            $adminNav = \XF::em()->create('XF:AdminNavigation');
            $adminNav->navigation_id = 'hardMOB_afiliados';
            $adminNav->parent_navigation_id = 'options';  // Coloque dentro do menu de opções existente
            $adminNav->display_order = 1000;
            $adminNav->link = 'options/groups/hardMOB_afiliados';
            $adminNav->icon = 'fa-link';
            $adminNav->admin_permission_id = 'option';
            $adminNav->debug_only = false;
            $adminNav->hide_no_children = false;
            $adminNav->save();
            
            // Criar frase para a navegação
            /** @var \XF\Entity\Phrase $navPhrase */
            $navPhrase = \XF::em()->create('XF:Phrase');
            $navPhrase->title = 'admin_navigation.hardMOB_afiliados';
            $navPhrase->language_id = 0;
            $navPhrase->addon_id = 'hardMOB/Afiliados';
            $navPhrase->phrase_text = 'Sistema de Afiliados';
            $navPhrase->save();
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
        // Remove opções e grupo de opções usando Entity API
        $options = \XF::finder('XF:Option')
            ->where('option_id', 'like', 'hardMOB_afiliados_%')
            ->fetch();
            
        foreach ($options as $option)
        {
            $option->delete();
        }
        
        $optionGroup = \XF::em()->find('XF:OptionGroup', 'hardMOB_afiliados');
        if ($optionGroup)
        {
            $optionGroup->delete();
        }
        
        // Remove navegação admin
        $adminNav = \XF::em()->find('XF:AdminNavigation', 'hardMOB_afiliados');
        if ($adminNav)
        {
            $adminNav->delete();
        }
        
        // Remove as frases
        $phrases = \XF::finder('XF:Phrase')
            ->where('title', 'like', ['option_group_hardMOB_afiliados%', 'option_hardMOB_afiliados%', 'admin_navigation.hardMOB_afiliados'])
            ->fetch();
            
        foreach ($phrases as $phrase)
        {
            $phrase->delete();
        }
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