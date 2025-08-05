<?php

namespace hardMOB\Afiliados;

use XF\Container;
use XF\Template\Templater;

class Listener
{
    public static function appSetup(\XF\App $app)
    {
        // Registra serviços customizados
        $container = $app->container();
        
        $container['hardMOB\Afiliados:Cache'] = function($c) use ($app)
        {
            $class = $app->extendClass('hardMOB\Afiliados\Cache\LinkCache');
            return new $class($app);
        };
        
        $container['hardMOB\Afiliados:Configuration'] = function($c) use ($app)
        {
            $class = $app->extendClass('hardMOB\Afiliados\Service\Configuration');
            return new $class($app);
        };
        
        $container['hardMOB\Afiliados:AffiliateGenerator'] = function($c) use ($app)
        {
            $class = $app->extendClass('hardMOB\Afiliados\Service\AffiliateGenerator');
            return new $class($app);
        };
        
        $container['hardMOB\Afiliados:Analytics'] = function($c) use ($app)
        {
            $class = $app->extendClass('hardMOB\Afiliados\Service\Analytics');
            return new $class($app);
        };
        
        $container['hardMOB\Afiliados:Stats'] = function($c) use ($app)
        {
            $class = $app->extendClass('hardMOB\Afiliados\Service\Stats');
            return new $class($app);
        };
    }

    public static function templaterSetup(\XF\Container $container, &$params)
    {
        // Simplified - disable complex template processing to avoid startup crashes
        return;
    }

    public static function bbCodeRenderComplete(\XF\BbCode\Renderer\AbstractRenderer $renderer, &$finalOutput, $containerTag, array $children, $option, array $context, array $options)
    {
        // Simplified - disable complex BBCode processing to avoid startup crashes
        return;
    }

    public static function criteriaUser($rule, array $data, \XF\Entity\User $user, &$returnValue)
    {
        switch ($rule) {
            case 'hardmob_affiliate_clicks':
                $clickCount = \XF::db()->fetchOne(
                    'SELECT COUNT(*) FROM xf_hardmob_affiliate_clicks WHERE user_id = ?',
                    $user->user_id
                );
                $returnValue = ($clickCount >= $data['clicks']);
                break;
        }
    }
}