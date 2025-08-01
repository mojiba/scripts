<?php

namespace hardMOB\Afiliados\Repository;

use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Repository;

class Store extends Repository
{
    public function findStoresForList()
    {
        return $this->finder('hardMOB\Afiliados:Store')
            ->setDefaultOrder('name');
    }

    public function findActiveStores()
    {
        return $this->finder('hardMOB\Afiliados:Store')
            ->where('status', 'active')
            ->setDefaultOrder('name');
    }

    public function getStoreByDomain($domain)
    {
        return $this->finder('hardMOB\Afiliados:Store')
            ->where('domain', $domain)
            ->where('status', 'active')
            ->fetchOne();
    }

    public function getStoreChoices()
    {
        $stores = $this->findActiveStores()->fetch();
        $choices = [];
        
        foreach ($stores as $store) {
            $choices[$store->store_id] = $store->name;
        }
        
        return $choices;
    }
}
