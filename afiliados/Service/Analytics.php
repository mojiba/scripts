<?php

namespace hardMOB\Afiliados\Service;

use XF\Service\AbstractService;

class Analytics extends AbstractService
{
    protected $errorHandler;

    public function __construct(\XF\App $app)
    {
        parent::__construct($app);
        $this->errorHandler = $app->service('hardMOB\Afiliados:ErrorHandler');
    }
    public function trackClick($storeId, $slug, $userId = null)
    {
        try {
            // Registra o clique na base de dados
            $click = $this->em()->create('hardMOB\Afiliados:Click');
            $click->store_id = $storeId;
            $click->slug = $slug;
            $click->user_id = $userId ?: 0;
            $click->save();

            // Envia para Google Analytics se configurado
            $this->sendToGoogleAnalytics($storeId, $slug, $userId);

            return $click;
        } catch (\Exception $e) {
            $this->errorHandler->logError('tracking_failed', 'Failed to track click', ['error' => $e->getMessage(), 'store_id' => $storeId, 'slug' => $slug], $e);
            // Return a dummy click object to avoid breaking the flow
            $click = $this->em()->create('hardMOB\Afiliados:Click');
            $click->store_id = $storeId;
            $click->slug = $slug;
            $click->user_id = $userId ?: 0;
            return $click;
        }
    }

    protected function sendToGoogleAnalytics($storeId, $slug, $userId = null)
    {
        try {
            $gaTrackingId = $this->app->options()->hardmob_afiliados_ga_tracking_id;
            if (!$gaTrackingId) {
                return; // GA not configured, silent return
            }

            $storeRepo = $this->repository('hardMOB\Afiliados:Store');
            $store = $storeRepo->find('hardMOB\Afiliados:Store', $storeId);
            
            if (!$store) {
                $this->errorHandler->logError('analytics_failed', 'Store not found for GA tracking', ['store_id' => $storeId]);
                return;
            }

            $data = [
                'v' => '1', // Version
                'tid' => $gaTrackingId, // Tracking ID
                't' => 'event', // Hit Type
                'ec' => 'Affiliate', // Event Category
                'ea' => 'Click', // Event Action
                'el' => $store->name . ':' . $slug, // Event Label
                'cid' => $this->getClientId($userId) // Client ID
            ];

            // Envia dados para GA
            $this->sendGARequest($data);
        } catch (\Exception $e) {
            $this->errorHandler->logError('analytics_failed', 'Failed to send GA tracking', ['error' => $e->getMessage(), 'store_id' => $storeId], $e);
        }
    }

    protected function getClientId($userId = null)
    {
        if ($userId) {
            return 'user_' . $userId;
        }
        
        // Gera um client ID baseado no IP e User Agent
        $request = $this->app->request();
        return md5($request->getIp() . $request->getServer('HTTP_USER_AGENT', ''));
    }

    protected function sendGARequest($data)
    {
        try {
            $url = 'https://www.google-analytics.com/collect';
            $content = http_build_query($data);

            $options = [
                'http' => [
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method' => 'POST',
                    'content' => $content,
                    'timeout' => 5
                ]
            ];

            $context = stream_context_create($options);
            
            $result = @file_get_contents($url, false, $context);
            if ($result === false) {
                throw new \Exception('Failed to send GA request');
            }
        } catch (\Exception $e) {
            $this->errorHandler->logError('analytics_failed', 'GA API request failed', ['error' => $e->getMessage()], $e);
        }
    }

    public function getConversionRate($storeId = null, $period = 'month')
    {
        $clickRepo = $this->repository('hardMOB\Afiliados:Click');
        
        $startDate = $this->getPeriodStartDate($period);
        
        $conditions = ['click_date >= ?'];
        $values = [$startDate];
        
        if ($storeId) {
            $conditions[] = 'store_id = ?';
            $values[] = $storeId;
        }
        
        $totalClicks = $this->db()->fetchOne('
            SELECT COUNT(*) 
            FROM xf_hardmob_affiliate_clicks 
            WHERE ' . implode(' AND ', $conditions),
            $values
        );
        
        // Para uma implementação completa, seria necessário integração com APIs
        // das lojas para obter dados de conversão reais
        
        return [
            'total_clicks' => $totalClicks,
            'estimated_conversions' => round($totalClicks * 0.05), // 5% estimado
            'conversion_rate' => $totalClicks > 0 ? '5.0%' : '0%'
        ];
    }

    protected function getPeriodStartDate($period)
    {
        switch ($period) {
            case 'day':
                return \XF::$time - 86400;
            case 'week':
                return \XF::$time - (7 * 86400);
            case 'month':
                return \XF::$time - (30 * 86400);
            case 'year':
                return \XF::$time - (365 * 86400);
            default:
                return \XF::$time - (30 * 86400);
        }
    }
}
