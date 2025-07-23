<?php

namespace hardMOB\AfiliateLinks\XF\Admin\Controller;

class Option extends XFCP_Option
{
    protected function setupOptionFilter(\XF\Entity\AddOn $addOn = null)
    {
        $return = parent::setupOptionFilter($addOn);
        
        if ($addOn && $addOn->addon_id == 'hardMOB/AfiliateLinks') {
            $return['group_id'] = 'hardMOBAfiliateLinks';
        }
        
        return $return;
    }
}