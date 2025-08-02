<?php

namespace hardMOB\Afiliados\Pub\Controller;

use XF\Pub\Controller\AbstractController;
use XF\Mvc\ParameterBag;

class Affiliate extends AbstractController
{
    public function actionIndex(ParameterBag $params)
    {
        $errorHandler = $this->service('hardMOB\Afiliados:ErrorHandler');
        $storeId = $params->store_id;
        $encodedSlug = $params->slug;
        
        if (!$storeId || !$encodedSlug) {
            $errorHandler->logError('invalid_slug', 'Missing store ID or slug parameters');
            return $this->notFound();
        }
        
        $slug = base64_decode($encodedSlug);
        if (!$slug) {
            $errorHandler->logError('invalid_slug', 'Invalid base64 encoded slug', ['slug' => $encodedSlug]);
            return $this->notFound();
        }
        
        try {
            $store = $this->assertStoreExists($storeId);
            
            // Registra o clique
            $analytics = $this->service('hardMOB\Afiliados:Analytics');
            $analytics->trackClick($storeId, $slug, \XF::visitor()->user_id);
            
            // Gera a URL final de afiliado
            $connector = $store->getConnectorClass();
            if (!$connector) {
                $errorHandler->logError('connector_missing', 'Store connector not found', ['store' => $store->name]);
                return $this->notFound();
            }
            
            if (!$connector->validateSlug($slug)) {
                $errorHandler->logError('invalid_slug', 'Slug validation failed for store connector', ['slug' => $slug, 'store' => $store->name]);
                return $this->notFound();
            }
            
            $affiliateUrl = $connector->generateAffiliateUrl($slug);
            
            // Faz o redirect 302
            return $this->redirect($affiliateUrl, '', 302);
            
        } catch (\Exception $e) {
            $errorHandler->logError('link_generation_failed', 'Failed to process affiliate link', ['slug' => $slug, 'error' => $e->getMessage()], $e);
            return $this->notFound();
        }
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
