<?php

namespace hardMOB\Afiliados\Service;

use XF\Service\AbstractService;
use hardMOB\Afiliados\Helper\SecurityValidator;

class Analytics extends AbstractService
{
    protected $securityValidator;

    public function __construct(\XF\App $app, $request = null)
    {
        parent::__construct($app, $request);
        $this->securityValidator = new SecurityValidator($app);
    }

    public function trackClick($storeId, $slug, $userId = null)
    {
        try {
            // Validate and sanitize inputs
            $storeId = filter_var($storeId, FILTER_VALIDATE_INT);
            if (!$storeId) {
                throw new \InvalidArgumentException('Invalid store ID');
            }

            $sanitizedSlug = $this->securityValidator->validateSlug($slug);
            if (empty($sanitizedSlug)) {
                throw new \InvalidArgumentException('Invalid slug');
            }

            $userId = $userId ? filter_var($userId, FILTER_VALIDATE_INT) : null;

            // Registra o clique na base de dados usando XenForo ORM (safe from SQL injection)
            $click = $this->em()->create('hardMOB\Afiliados:Click');
            $click->store_id = $storeId;
            $click->slug = $sanitizedSlug;
            $click->user_id = $userId ?: 0;
            $click->save();

            // Envia para Google Analytics se configurado
            $this->sendToGoogleAnalytics($storeId, $sanitizedSlug, $userId);

            return $click;
        } catch (\Exception $e) {
            \XF::logError('Analytics tracking error: ' . $e->getMessage());
            return null;
        }
    }

    protected function sendToGoogleAnalytics($storeId, $slug, $userId = null)
    {
        $gaTrackingId = $this->app->options()->hardmob_afiliados_ga_tracking_id;
        if (!$gaTrackingId) {
            return;
        }

        // Validate tracking ID format
        if (!preg_match('/^UA-\d+-\d+$/', $gaTrackingId)) {
            \XF::logError('Invalid Google Analytics tracking ID format');
            return;
        }

        $storeRepo = $this->repository('hardMOB\Afiliados:Store');
        $store = $storeRepo->find('hardMOB\Afiliados:Store', (int) $storeId);
        
        if (!$store) {
            return;
        }

        // Sanitize data for GA
        $storeName = preg_replace('/[^a-zA-Z0-9_-]/', '', $store->name);
        $sanitizedSlug = preg_replace('/[^a-zA-Z0-9_\/.:-]/', '', $slug);

        $data = [
            'v' => '1', // Version
            'tid' => $gaTrackingId, // Tracking ID
            't' => 'event', // Hit Type
            'ec' => 'Affiliate', // Event Category
            'ea' => 'Click', // Event Action
            'el' => $storeName . ':' . substr($sanitizedSlug, 0, 100), // Event Label (limited length)
            'cid' => $this->getClientId($userId) // Client ID
        ];

        // Envia dados para GA
        $this->sendGARequest($data);
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
        
        try {
            @file_get_contents($url, false, $context);
        } catch (\Exception $e) {
            // Ignora erros de GA para não afetar o funcionamento principal
        }
    }

    public function getConversionRate($storeId = null, $period = 'month')
    {
        $clickRepo = $this->repository('hardMOB\Afiliados:Click');
        
        // Validate inputs
        if ($storeId !== null) {
            $storeId = filter_var($storeId, FILTER_VALIDATE_INT);
            if (!$storeId) {
                throw new \InvalidArgumentException('Invalid store ID');
            }
        }

        $allowedPeriods = ['day', 'week', 'month', 'year'];
        if (!in_array($period, $allowedPeriods)) {
            $period = 'month';
        }
        
        $startDate = $this->getPeriodStartDate($period);
        
        // Use parameterized query to prevent SQL injection
        $conditions = ['click_date >= ?'];
        $values = [(int) $startDate];
        
        if ($storeId) {
            $conditions[] = 'store_id = ?';
            $values[] = (int) $storeId;
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
            'total_clicks' => (int) $totalClicks,
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
