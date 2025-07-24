<?php

namespace hardMOB\AfiliateLinks\Listener;

use XF\Container;
use XF\Template\Templater;

class ContentRender
{
    /**
     * Listener para BbCode:renderTag
     */
    public static function renderTagUrl($tagChildren, $tagOption, $tag, array $options, Templater $templater)
    {
        if (empty($tagOption)) {
            return null;
        }

        $url = $tagOption;
        
        /** @var \hardMOB\AfiliateLinks\Service\LinkProcessor $linkProcessor */
        $linkProcessor = \XF::service('hardMOB\AfiliateLinks:LinkProcessor');
        
        $processedUrl = $linkProcessor->processUrl($url);
        if ($processedUrl !== $url) {
            $parseHost = parse_url($processedUrl, PHP_URL_HOST);
            $host = preg_replace('#^www\.#i', '', strtolower($parseHost));
            
            // Define custom attributes for the link
            $options['attributes']['class'] = (empty($options['attributes']['class']) ? 'link-afiliado' : $options['attributes']['class'] . ' link-afiliado');
            $options['attributes']['data-loja'] = $host;
            $options['attributes']['target'] = '_blank';
            $options['attributes']['rel'] = 'noopener nofollow';
            
            // Adicione tracking de analytics se ativado
            if (\XF::options()->hmaf_enable_analytics) {
                $category = \XF::options()->hmaf_analytics_category ?: 'Afiliados';
                $action = \XF::options()->hmaf_analytics_action ?: 'Clique';
                $label = addslashes("$host | " . substr($url, 0, 50) . (strlen($url) > 50 ? '...' : ''));
                
                // GA4
                $options['attributes']['onclick'] = "gtag('event','click',{'event_category':'$category','event_label':'$label'});";
            }
            
            // Substitui a URL original pela URL com afiliado
            $tagOption = $processedUrl;
        }
        
        return null;
    }
    
    /**
     * Listener para o template de renderização de HTML
     */
    public static function templateHook($hookName, &$contents, array $hookParams, Templater $templater)
    {
        if ($hookName == 'bb_code_tag_url_output') {
            $urlRegex = '/<a\s+href="([^"]+)"/i';
            if (preg_match($urlRegex, $contents, $matches)) {
                $url = $matches[1];
                
                /** @var \hardMOB\AfiliateLinks\Service\LinkProcessor $linkProcessor */
                $linkProcessor = \XF::service('hardMOB\AfiliateLinks:LinkProcessor');
                
                $processedUrl = $linkProcessor->processUrl($url);
                if ($processedUrl !== $url) {
                    $parseHost = parse_url($processedUrl, PHP_URL_HOST);
                    $host = preg_replace('#^www\.#i', '', strtolower($parseHost));
                    
                    $contents = str_replace(
                        '<a href="' . $url . '"', 
                        '<a href="' . $processedUrl . '" class="link-afiliado" data-loja="' . $host . '" target="_blank" rel="noopener nofollow"', 
                        $contents
                    );
                    
                    // Adicione tracking de analytics se ativado
                    if (\XF::options()->hmaf_enable_analytics) {
                        $category = \XF::options()->hmaf_analytics_category ?: 'Afiliados';
                        $action = \XF::options()->hmaf_analytics_action ?: 'Clique';
                        $label = addslashes("$host | " . substr($url, 0, 50) . (strlen($url) > 50 ? '...' : ''));
                        
                        // Adicionar atributo onclick
                        $contents = str_replace(
                            'class="link-afiliado"',
                            'class="link-afiliado" onclick="gtag(\'event\',\'click\',{\'event_category\':\'' . $category . '\',\'event_label\':\'' . $label . '\'});"',
                            $contents
                        );
                    }
                }
            }
        }
    }
}