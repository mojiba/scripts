<?php

namespace hardMOB\Afiliados\Pub\Controller;

use XF\Pub\Controller\AbstractController;
use XF\Mvc\ParameterBag;
use hardMOB\Afiliados\Helper\SecurityValidator;

class Affiliate extends AbstractController
{
    protected $securityValidator;

    public function __construct(\XF\App $app, \XF\Mvc\Request $request, \XF\Mvc\Response $response, \XF\Mvc\RouteMatch $routeMatch)
    {
        parent::__construct($app, $request, $response, $routeMatch);
        $this->securityValidator = new SecurityValidator($app);
    }

    public function actionIndex(ParameterBag $params)
    {
        $storeId = $params->store_id;
        $encodedSlug = $params->slug;
        
        if (!$storeId || !$encodedSlug) {
            return $this->notFound();
        }

        // Validate store ID as integer
        $storeId = filter_var($storeId, FILTER_VALIDATE_INT);
        if (!$storeId) {
            return $this->notFound();
        }
        
        // Validate and decode slug safely
        $slug = $this->securityValidator->validateBase64($encodedSlug, 2048);
        if (empty($slug)) {
            \XF::logError('Invalid base64 slug provided: ' . substr($encodedSlug, 0, 100));
            return $this->notFound();
        }
        
        $store = $this->assertStoreExists($storeId);
        
        // Registra o clique
        $analytics = $this->service('hardMOB\Afiliados:Analytics');
        $analytics->trackClick($storeId, $slug, \XF::visitor()->user_id);
        
        // Gera a URL final de afiliado
        $connector = $store->getConnectorClass();
        
        // Additional validation on the connector level
        if (!$connector->validateSlug($slug)) {
            \XF::logError('Invalid slug for store connector: ' . substr($slug, 0, 100));
            return $this->notFound();
        }
        
        $affiliateUrl = $connector->generateAffiliateUrl($slug);
        
        // Validate generated URL before redirect
        $validatedUrl = $this->securityValidator->validateUrl($affiliateUrl);
        if (empty($validatedUrl)) {
            \XF::logError('Invalid affiliate URL generated: ' . substr($affiliateUrl, 0, 100));
            return $this->notFound();
        }
        
        // Faz o redirect 302
        return $this->redirect($validatedUrl, '', 302);
    }

    protected function assertStoreExists($id)
    {
        // Validate ID is integer
        $id = filter_var($id, FILTER_VALIDATE_INT);
        if (!$id) {
            throw $this->exception($this->notFound(\XF::phrase('requested_store_not_found')));
        }

        $store = $this->em()->find('hardMOB\Afiliados:Store', $id);
        if (!$store || $store->status !== 'active') {
            throw $this->exception($this->notFound(\XF::phrase('requested_store_not_found')));
        }
        return $store;
    }
}
