<?php

namespace hardMOB\Afiliados\Service;

use XF\Service\AbstractService;

class Stats extends AbstractService
{
    public function getOverallStats()
    {
        $db = $this->db();
        
        $stats = [
            'total_stores' => $db->fetchOne('SELECT COUNT(*) FROM xf_hardmob_affiliate_stores'),
            'active_stores' => $db->fetchOne('SELECT COUNT(*) FROM xf_hardmob_affiliate_stores WHERE status = "active"'),
            'total_clicks' => $db->fetchOne('SELECT COUNT(*) FROM xf_hardmob_affiliate_clicks'),
            'clicks_today' => $db->fetchOne('SELECT COUNT(*) FROM xf_hardmob_affiliate_clicks WHERE click_date >= ?', \XF::$time - 86400),
            'unique_users' => $db->fetchOne('SELECT COUNT(DISTINCT user_id) FROM xf_hardmob_affiliate_clicks WHERE user_id > 0'),
            'cache_entries' => $db->fetchOne('SELECT COUNT(*) FROM xf_hardmob_affiliate_cache')
        ];
        
        return $stats;
    }

    public function getFilteredStats($filters = [])
    {
        $db = $this->db();
        
        $conditions = [];
        $values = [];
        
        // Filtro por período
        if (!empty($filters['period'])) {
            $startDate = $this->getPeriodStartDate($filters['period']);
            $conditions[] = 'c.click_date >= ?';
            $values[] = $startDate;
        }
        
        // Filtro por loja
        if (!empty($filters['store_id'])) {
            $conditions[] = 'c.store_id = ?';
            $values[] = $filters['store_id'];
        }
        
        // Filtro por usuário
        if (!empty($filters['user_id'])) {
            $conditions[] = 'c.user_id = ?';
            $values[] = $filters['user_id'];
        }
        
        $whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        
        // Estatísticas por loja
        $storeStats = $db->fetchAll("
            SELECT 
                s.store_id,
                s.name as store_name,
                COUNT(c.click_id) as total_clicks,
                COUNT(DISTINCT c.user_id) as unique_users,
                COUNT(DISTINCT DATE(FROM_UNIXTIME(c.click_date))) as active_days
            FROM xf_hardmob_affiliate_stores s
            LEFT JOIN xf_hardmob_affiliate_clicks c ON s.store_id = c.store_id
            {$whereClause}
            GROUP BY s.store_id, s.name
            ORDER BY total_clicks DESC
        ", $values);
        
        // Top slugs
        $topSlugs = $db->fetchAll("
            SELECT 
                c.slug,
                s.name as store_name,
                COUNT(*) as click_count
            FROM xf_hardmob_affiliate_clicks c
            INNER JOIN xf_hardmob_affiliate_stores s ON c.store_id = s.store_id
            {$whereClause}
            GROUP BY c.slug, s.name
            ORDER BY click_count DESC
            LIMIT 20
        ", $values);
        
        // Clicks por dia
        $dailyClicks = $db->fetchAll("
            SELECT 
                DATE(FROM_UNIXTIME(c.click_date)) as click_date,
                COUNT(*) as click_count
            FROM xf_hardmob_affiliate_clicks c
            {$whereClause}
            GROUP BY DATE(FROM_UNIXTIME(c.click_date))
            ORDER BY click_date DESC
            LIMIT 30
        ", $values);
        
        return [
            'store_stats' => $storeStats,
            'top_slugs' => $topSlugs,
            'daily_clicks' => array_reverse($dailyClicks) // Ordem cronológica
        ];
    }

    public function getChartData($type = 'daily', $storeId = null, $period = 'month')
    {
        $db = $this->db();
        $startDate = $this->getPeriodStartDate($period);
        
        $conditions = ['click_date >= ?'];
        $values = [$startDate];
        
        if ($storeId) {
            $conditions[] = 'store_id = ?';
            $values[] = $storeId;
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $conditions);
        
        switch ($type) {
            case 'hourly':
                $sql = "
                    SELECT 
                        HOUR(FROM_UNIXTIME(click_date)) as period,
                        COUNT(*) as clicks
                    FROM xf_hardmob_affiliate_clicks
                    {$whereClause}
                    GROUP BY HOUR(FROM_UNIXTIME(click_date))
                    ORDER BY period
                ";
                break;
                
            case 'daily':
            default:
                $sql = "
                    SELECT 
                        DATE(FROM_UNIXTIME(click_date)) as period,
                        COUNT(*) as clicks
                    FROM xf_hardmob_affiliate_clicks
                    {$whereClause}
                    GROUP BY DATE(FROM_UNIXTIME(click_date))
                    ORDER BY period
                ";
                break;
        }
        
        $results = $db->fetchAll($sql, $values);
        
        $labels = [];
        $data = [];
        
        foreach ($results as $result) {
            $labels[] = $result['period'];
            $data[] = $result['clicks'];
        }
        
        return [
            'labels' => $labels,
            'data' => $data
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
