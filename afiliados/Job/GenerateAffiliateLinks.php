<?php

namespace hardMOB\Afiliados\Job;

use XF\Job\AbstractJob;

class GenerateAffiliateLinks extends AbstractJob
{
    protected $defaultData = [
        'start' => 0,
        'batch' => 100
    ];

    public function run($maxRunTime)
    {
        $startTime = microtime(true);
        
        if (!$this->app->options()->hardmob_afiliados_enable_cron) {
            return $this->complete();
        }

        $affiliateGenerator = $this->app->service('hardMOB\Afiliados:AffiliateGenerator');
        
        try {
            $generated = $affiliateGenerator->preGenerateLinks();
            
            $this->data['start'] += $this->data['batch'];
            
            // Log da execução
            \XF::logError(sprintf(
                'hardMOB Afiliados: Generated %d affiliate links in %.2f seconds',
                $generated,
                microtime(true) - $startTime
            ), false);
            
            return $this->complete();
            
        } catch (\Exception $e) {
            \XF::logException($e);
            
            // Em caso de erro, tenta novamente mais tarde
            if (microtime(true) - $startTime < $maxRunTime - 1) {
                return $this->resume();
            }
            
            return $this->complete();
        }
    }

    public function getStatusMessage()
    {
        return 'Generating affiliate links...';
    }

    public function canCancel()
    {
        return true;
    }

    public function canTriggerByChoice()
    {
        return true;
    }
}
