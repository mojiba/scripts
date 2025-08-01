<?php

namespace hardMOB\Afiliados\Cron;

class GenerateLinks
{
    public static function runHourly()
    {
        $app = \XF::app();
        
        if (!$app->options()->hardmob_afiliados_enable_cron)
        {
            return;
        }

        $jobManager = $app->jobManager();
        $jobManager->enqueueUnique(
            'hardmobAfiliados_generateLinks',
            'hardMOB\Afiliados:GenerateAffiliateLinks',
            [],
            false
        );
    }

    public static function runDaily()
    {
        $app = \XF::app();
        
        // Clean expired cache entries
        $cacheService = $app->service('hardMOB\Afiliados:Cache');
        $cacheService->cleanExpired();
        
        // Clean old click records (older than 1 year)
        $cutoff = \XF::$time - (365 * 24 * 3600);
        $app->db()->delete('xf_hardmob_affiliate_clicks', 'click_date < ?', $cutoff);
    }
}
