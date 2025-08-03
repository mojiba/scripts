<?php

namespace hardMOB\Afiliados;

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
        
        $container['hardMOB\Afiliados:AffiliateGenerator'] = function($c) use ($app)
        {
            $class = $app->extendClass('hardMOB\Afiliados\Service\AffiliateGenerator');
            return new $class($app, $app);
        };
        
        $container['hardMOB\Afiliados:Analytics'] = function($c) use ($app)
        {
            $class = $app->extendClass('hardMOB\Afiliados\Service\Analytics');
            return new $class($app, $app);
        };
        
        $container['hardMOB\Afiliados:Stats'] = function($c) use ($app)
        {
            $class = $app->extendClass('hardMOB\Afiliados\Service\Stats');
            return new $class($app, $app);
        };
    }

    public static function templaterSetup(Templater $templater)
    {
        // Adiciona função personalizada para processar links de afiliados
        $templater->addFunction('affiliate_links', function($templater, &$escape, $text, $userId = null)
        {
            $affiliateGenerator = \XF::app()->service('hardMOB\Afiliados:AffiliateGenerator');
            $escape = false;
            return $affiliateGenerator->processText($text, $userId);
        });
    }

    public static function bbCodeRenderComplete(\XF\BbCode\Renderer\AbstractRenderer $renderer, &$finalOutput, $containerTag, array $children, $option, array $context, array $options)
    {
        if (!($renderer instanceof \XF\BbCode\Renderer\Html)) {
            return;
        }

        // Processa links de afiliados no conteúdo renderizado
        $userId = isset($context['user']) ? $context['user']->user_id : null;
        $affiliateGenerator = \XF::app()->service('hardMOB\Afiliados:AffiliateGenerator');
        $finalOutput = $affiliateGenerator->processText($finalOutput, $userId);
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
