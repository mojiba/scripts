<?php

namespace hardMOB\Afiliados\Repository;

use XF\Mvc\Entity\Repository;
use XF\Mvc\Entity\Finder;

class Store extends Repository
{
    /**
     * Find stores for list display
     */
    public function findStoresForList()
    {
        return $this->finder('hardMOB\Afiliados:Store')
            ->order('name', 'ASC');
    }

    /**
     * Find active stores with caching
     */
    public function findActiveStores($useCache = true)
    {
        if ($useCache) {
            $cache = $this->app()->cache();
            if ($cache) {
                $stores = $cache->fetch('hardmob_affiliate_active_stores');
                if ($stores !== false) {
                    // Convert back to collection
                    $storeEntities = [];
                    foreach ($stores as $storeData) {
                        $store = $this->em->instantiateEntity('hardMOB\Afiliados:Store', $storeData);
                        $store->setReadOnly(true);
                        $storeEntities[] = $store;
                    }
                    return $this->em->getBasicCollection($storeEntities);
                }
            }
        }

        $finder = $this->finder('hardMOB\Afiliados:Store')
            ->where('status', 'active')
            ->order('name', 'ASC');
        
        $stores = $finder->fetch();

        if ($useCache && $cache) {
            // Cache the store data
            $storeData = $stores->toArray();
            $cache->save('hardmob_affiliate_active_stores', $storeData, 3600);
        }

        return $stores;
    }

    /**
     * Get store by domain
     */
    public function getStoreByDomain($domain)
    {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('#^https?://#i', '', $domain);
        $domain = rtrim($domain, '/');

        return $this->finder('hardMOB\Afiliados:Store')
            ->where('domain', $domain)
            ->where('status', 'active')
            ->fetchOne();
    }

    /**
     * Get store choices for forms
     */
    public function getStoreChoices()
    {
        $stores = $this->findActiveStores()->fetch();
        $choices = [];
        
        foreach ($stores as $store) {
            $choices[$store->store_id] = $store->name;
        }
        
        return $choices;
    }

    /**
     * Get store statistics
     */
    public function getStoreStats($storeId = null, $period = 'month')
    {
        $startDate = $this->getPeriodStartDate($period);
        
        $conditions = ['click_date >= ?'];
        $values = [$startDate];
        
        if ($storeId) {
            $conditions[] = 'store_id = ?';
            $values[] = $storeId;
        }
        
        return $this->db()->fetchRow('
            SELECT 
                COUNT(*) as total_clicks,
                COUNT(DISTINCT user_id) as unique_users,
                COUNT(DISTINCT ip_address) as unique_ips,
                AVG(CASE WHEN user_id > 0 THEN 1 ELSE 0 END) as registered_user_rate
            FROM xf_hardmob_affiliate_clicks 
            WHERE ' . implode(' AND ', $conditions),
            $values
        );
    }

    /**
     * Get top performing stores
     */
    public function getTopStores($limit = 10, $period = 'month')
    {
        $startDate = $this->getPeriodStartDate($period);
        
        $storeStats = $this->db()->fetchAll('
            SELECT 
                s.store_id,
                s.name,
                s.domain,
                COUNT(c.click_id) as click_count,
                COUNT(DISTINCT c.user_id) as unique_users
            FROM xf_hardmob_affiliate_stores s
            LEFT JOIN xf_hardmob_affiliate_clicks c ON (s.store_id = c.store_id AND c.click_date >= ?)
            WHERE s.status = ?
            GROUP BY s.store_id
            ORDER BY click_count DESC
            LIMIT ?
        ', [$startDate, 'active', $limit]);

        return $storeStats;
    }

    /**
     * Search stores
     */
    public function findStoresForSearch($search, $status = null)
    {
        $finder = $this->finder('hardMOB\Afiliados:Store');
        
        if (!empty($search)) {
            $finder->where([
                ['name', 'LIKE', '%' . $search . '%'],
                'OR',
                ['domain', 'LIKE', '%' . $search . '%']
            ]);
        }
        
        if ($status !== null) {
            $finder->where('status', $status);
        }
        
        return $finder->order('name', 'ASC');
    }

    /**
     * Bulk update store status
     */
    public function bulkUpdateStatus(array $storeIds, $status)
    {
        if (empty($storeIds) || !in_array($status, ['active', 'inactive'])) {
            return false;
        }

        $affected = $this->db()->update(
            'xf_hardmob_affiliate_stores',
            ['status' => $status, 'modified_date' => \XF::$time],
            'store_id IN (' . $this->db()->quote($storeIds) . ')'
        );

        // Clear cache
        $cache = $this->app()->cache();
        if ($cache) {
            $cache->delete('hardmob_affiliate_active_stores');
        }

        return $affected;
    }

    /**
     * Get period start date
     */
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

    /**
     * Clean up orphaned data
     */
    public function cleanupOrphanedData()
    {
        // Clean up clicks for deleted stores
        $orphanedClicks = $this->db()->delete('
            xf_hardmob_affiliate_clicks c
            LEFT JOIN xf_hardmob_affiliate_stores s ON c.store_id = s.store_id
            WHERE s.store_id IS NULL
        ');

        return ['orphaned_clicks' => $orphanedClicks];
    }
}
