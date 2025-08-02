<?php

namespace hardMOB\Afiliados;

use XF\Template\Templater;
use hardMOB\Afiliados\Helper\SecurityValidator;

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

    public static function templateerSetup(Templater $templater)
    {
        // Adiciona função personalizada para processar links de afiliados
        $templater->addFunction('affiliate_links', function($templater, &$escape, $text, $userId = null)
        {
            try {
                $affiliateGenerator = \XF::app()->service('hardMOB\Afiliados:AffiliateGenerator');
                $securityValidator = new SecurityValidator(\XF::app());
                
                // Sanitize output for template to prevent XSS
                $processedText = $affiliateGenerator->processText($text, $userId);
                $escape = false; // We're handling escaping in the security validator
                
                return $securityValidator->sanitizeOutput($processedText);
            } catch (\Exception $e) {
                \XF::logError('Template affiliate link processing error: ' . $e->getMessage());
                return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        });
    }

    public static function bbCodeRenderComplete(\XF\BbCode\Renderer\AbstractRenderer $renderer, &$finalOutput, $containerTag, array $children, $option, array $context, array $options)
    {
        if (!($renderer instanceof \XF\BbCode\Renderer\Html)) {
            return;
        }

        try {
            // Processa links de afiliados no conteúdo renderizado
            $userId = isset($context['user']) ? $context['user']->user_id : null;
            $affiliateGenerator = \XF::app()->service('hardMOB\Afiliados:AffiliateGenerator');
            $securityValidator = new SecurityValidator(\XF::app());
            
            $processedOutput = $affiliateGenerator->processText($finalOutput, $userId);
            $finalOutput = $securityValidator->sanitizeOutput($processedOutput);
        } catch (\Exception $e) {
            \XF::logError('BBCode affiliate link processing error: ' . $e->getMessage());
            // Keep original output if processing fails
        }
    }

    public static function criteriaUser($rule, array $data, \XF\Entity\User $user, &$returnValue)
    {
        switch ($rule) {
            case 'hardmob_affiliate_clicks':
                // Validate user ID and clicks parameter
                $userId = filter_var($user->user_id, FILTER_VALIDATE_INT);
                $clicksThreshold = isset($data['clicks']) ? filter_var($data['clicks'], FILTER_VALIDATE_INT) : 0;
                
                if (!$userId || $clicksThreshold === false) {
                    $returnValue = false;
                    break;
                }

                $clickCount = \XF::db()->fetchOne(
                    'SELECT COUNT(*) FROM xf_hardmob_affiliate_clicks WHERE user_id = ?',
                    $userId
                );
                $returnValue = ((int) $clickCount >= $clicksThreshold);
                break;
        }
    }
}
