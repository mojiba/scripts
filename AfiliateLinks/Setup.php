<?php

namespace hardMOB\AfiliateLinks;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;

class Setup extends AbstractSetup
{
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;

    public function installStep1()
    {
        // Criar grupo de opções
        $this->addOptionGroup('hardMOBAfiliateLinks', 'hardmob-afiliate-links');
        
        // Para campos de texto grande, use 'string' como tipo de dados, mas 'textarea' como formato de edição
        $this->addOption('hmaf_stores_list', '', 'string', 'textarea');
        $this->addOption('hmaf_enable_analytics', '1', 'boolean', 'onoff');
        $this->addOption('hmaf_analytics_category', 'Afiliados', 'string', 'textbox');
        $this->addOption('hmaf_analytics_action', 'Click', 'string', 'textbox');
        $this->addOption('hmaf_aliexpress_enabled', '1', 'boolean', 'onoff');
        $this->addOption('hmaf_aliexpress_app_key', '', 'string', 'textbox');
        $this->addOption('hmaf_aliexpress_app_secret', '', 'string', 'textbox');
        $this->addOption('hmaf_aliexpress_tracking_id', '', 'string', 'textbox');
    }

    protected function addOptionGroup($groupId, $displayOrder, $debug = false)
    {
        $entity = \XF::em()->create('XF:OptionGroup');
        $entity->option_group_id = $groupId;
        $entity->display_order = $displayOrder;
        $entity->debug_only = $debug;
        $entity->addon_id = $this->addOn->getAddOnId();
        $entity->save();
    }

    protected function addOption($name, $value, $dataType, $editFormat)
    {
        $option = \XF::em()->create('XF:Option');
        $option->option_id = $name;
        $option->option_value = $value;
        $option->default_value = $value;
        $option->edit_format = $editFormat;
        $option->data_type = $dataType;
        $option->option_group_id = 'hardMOBAfiliateLinks'; // Adicionando o grupo às opções
        $option->addon_id = $this->addOn->getAddOnId();
        $option->save();
    }

    public function upgrade2010000Step1()
    {
        // Código de upgrade se necessário
    }
}