<?php

namespace hardMOB\Afiliados\Pub\Controller;

use XF\Pub\Controller\AbstractController;
use XF\Mvc\ParameterBag;

class Affiliate extends AbstractController
{
    public function actionIndex(ParameterBag $params)
    {
        $storeId = $params->store_id;
        $encodedSlug = $params->slug;
        
        if (!$storeId || !$encodedSlug) {
            return $this->notFound();
        }
        
        $slug = base64_decode($encodedSlug);
        if (!$slug) {
            return $this->notFound();
        }
        
        $store = $this->assertStoreExists($storeId);
        
        // Registra o clique
        $analytics = $this->service('hardMOB\Afiliados:Analytics');
        $analytics->trackClick($storeId, $slug, \XF::visitor()->user_id);
        
        // Gera a URL final de afiliado
        $connector = $store->getConnectorClass();
        $affiliateUrl = $connector->generateAffiliateUrl($slug);
        
        // Faz o redirect 302
        return $this->redirect($affiliateUrl, '', 302);
    }

    protected function assertStoreExists($id)
    {
        $store = $this->em()->find('hardMOB\Afiliados:Store', $id);
        if (!$store || $store->status !== 'active') {
            throw $this->exception($this->notFound(\XF::phrase('requested_store_not_found')));
        }
        return $store;
    }
}
