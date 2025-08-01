<?php

namespace hardMOB\Afiliados\Repository;

use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Repository;

class Click extends Repository
{
    public function findClicksForPeriod($startDate = null, $endDate = null)
    {
        $finder = $this->finder('hardMOB\Afiliados:Click');
        
        if ($startDate) {
            $finder->where('click_date', '>=', $startDate);
        }
        
        if ($endDate) {
            $finder->where('click_date', '<=', $endDate);
        }
        
        return $finder->setDefaultOrder('click_date', 'DESC');
    }

    public function getClickStatsByStore($storeId = null, $period = 'month')
    {
        $startDate = $this->getPeriodStartDate($period);
        
        $finder = $this->finder('hardMOB\Afiliados:Click')
            ->where('click_date', '>=', $startDate);
            
        if ($storeId) {
            $finder->where('store_id', $storeId);
        }
        
        return $finder
            ->with('Store')
            ->fetch()
            ->groupBy('store_id');
    }

    public function getTopClickedSlugs($limit = 10, $storeId = null, $period = 'month')
    {
        $startDate = $this->getPeriodStartDate($period);
        
        $conditions = ['click_date >= ?'];
        $values = [$startDate];
        
        if ($storeId) {
            $conditions[] = 'store_id = ?';
            $values[] = $storeId;
        }
        
        return $this->db()->fetchAll('
            SELECT slug, store_id, COUNT(*) as click_count
            FROM xf_hardmob_affiliate_clicks
            WHERE ' . implode(' AND ', $conditions) . '
            GROUP BY slug, store_id
            ORDER BY click_count DESC
            LIMIT ?
        ', array_merge($values, [$limit]));
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
