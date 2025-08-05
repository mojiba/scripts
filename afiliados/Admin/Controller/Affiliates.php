<?php

namespace hardMOB\Afiliados\Admin\Controller;

use XF\Admin\Controller\AbstractController;
use XF\Mvc\ParameterBag;
use hardMOB\Afiliados\Helper\Security;
use hardMOB\Afiliados\Entity\AuditLog;

class Affiliates extends AbstractController
{
    protected function preDispatchController($action, ParameterBag $params)
    {
        $this->assertAdminPermission('hardmob_afiliados');
        
        // Rate limiting for admin actions
        $rateLimitKey = 'admin_affiliate_' . \XF::visitor()->user_id . '_' . $action;
        if (!Security::checkRateLimit($rateLimitKey, 100, 3600)) {
            throw $this->exception($this->error(\XF::phrase('hardmob_afiliados_rate_limit_exceeded')));
        }
        
        // Log admin access
        AuditLog::logEvent(
            AuditLog::EVENT_ACCESS,
            'AdminController',
            0,
            "Admin accessed affiliate controller action: {$action}"
        );
    }

    public function actionIndex()
    {
        $storeRepo = $this->getStoreRepo();
        $stores = $storeRepo->findStoresForList()->fetch();

        // Get statistics for dashboard
        $analytics = $this->app()->service('hardMOB\Afiliados:Analytics');
        $totalStats = $analytics->getConversionRate(null, 'month');
        
        $viewParams = [
            'stores' => $stores,
            'totalStats' => $totalStats,
            'totalStores' => count($stores),
            'activeStores' => count(array_filter($stores->toArray(), function($store) {
                return $store['status'] === 'active';
            }))
        ];

        return $this->view('hardMOB\Afiliados:Store\List', 'hardmob_afiliados_store_list', $viewParams);
    }

    public function actionAdd()
    {
        $store = $this->em()->create('hardMOB\Afiliados:Store');
        return $this->storeAddEdit($store);
    }

    public function actionEdit(ParameterBag $params)
    {
        $store = $this->assertStoreExists($params->store_id);
        return $this->storeAddEdit($store);
    }

    protected function storeAddEdit(\hardMOB\Afiliados\Entity\Store $store)
    {
        $viewParams = [
            'store' => $store,
            'allowedDomains' => Security::getAllowedDomains()
        ];

        return $this->view('hardMOB\Afiliados:Store\Edit', 'hardmob_afiliados_store_edit', $viewParams);
    }

    public function actionSave(ParameterBag $params)
    {
        $this->assertPostOnly();
        
        // CSRF Protection
        if (!$this->validateCsrfToken($this->filter('_xfToken', 'str'))) {
            throw $this->exception($this->error(\XF::phrase('something_went_wrong_please_try_again')));
        }

        if ($params->store_id)
        {
            $store = $this->assertStoreExists($params->store_id);
        }
        else
        {
            $store = $this->em()->create('hardMOB\Afiliados:Store');
        }

        $this->storeSaveProcess($store)->run();

        return $this->redirect($this->buildLink('affiliates') . $this->buildLinkHash($store->store_id));
    }

    protected function storeSaveProcess(\hardMOB\Afiliados\Entity\Store $store)
    {
        $form = $this->formAction();

        $input = $this->filter([
            'name' => 'str',
            'domain' => 'str',
            'affiliate_code' => 'str',
            'status' => 'str'
        ]);

        // Additional security validation
        if (!empty($input['name'])) {
            $input['name'] = Security::sanitizeInput($input['name']);
        }
        
        if (!empty($input['domain'])) {
            $input['domain'] = Security::sanitizeInput($input['domain'], 'domain');
            if (!Security::validateDomain($input['domain'])) {
                $form->logError(\XF::phrase('hardmob_afiliados_please_enter_valid_domain'));
                return $form;
            }
        }
        
        if (!empty($input['affiliate_code'])) {
            $input['affiliate_code'] = Security::sanitizeInput($input['affiliate_code']);
            if (!Security::validateAffiliateCode($input['affiliate_code'])) {
                $form->logError(\XF::phrase('hardmob_afiliados_invalid_affiliate_code'));
                return $form;
            }
        }

        $form->basicEntitySave($store, $input);

        $form->complete(function() use ($store)
        {
            if (!$store->isUpdate())
            {
                $this->createConnectorStub($store);
            }
        });

        return $form;
    }

    protected function createConnectorStub(\hardMOB\Afiliados\Entity\Store $store)
    {
        $connectorName = preg_replace('/[^a-zA-Z0-9]/', '', $store->name);
        $connectorPath = \XF::getRootDirectory() . '/src/addons/hardMOB/Afiliados/Connector/' . $connectorName . '.php';

        if (!file_exists($connectorPath))
        {
            $template = $this->getConnectorTemplate($connectorName, $store);
            
            // Ensure directory exists
            $connectorDir = dirname($connectorPath);
            if (!is_dir($connectorDir)) {
                mkdir($connectorDir, 0755, true);
            }
            
            file_put_contents($connectorPath, $template);
            
            // Log connector creation
            AuditLog::logEvent(
                AuditLog::EVENT_CREATE,
                'Connector',
                $store->store_id,
                'Connector stub created for store: ' . $store->name
            );
        }
    }

    protected function getConnectorTemplate($className, \hardMOB\Afiliados\Entity\Store $store)
    {
        return "<?php

namespace hardMOB\\Afiliados\\Connector;

use hardMOB\\Afiliados\\Helper\\Security;

class {$className} implements StoreInterface
{
    protected \$store;

    public function __construct(\\hardMOB\\Afiliados\\Entity\\Store \$store)
    {
        \$this->store = \$store;
    }

    public function generateAffiliateUrl(\$slug)
    {
        // Validate slug first
        if (!Security::validateSlug(\$slug)) {
            throw new \\InvalidArgumentException('Invalid slug provided');
        }
        
        // Sanitize the slug
        \$slug = Security::sanitizeInput(\$slug, 'url');
        
        // Implementar lógica específica para {$store->name}
        // Exemplo: https://{$store->domain}/produto/\$slug?tag={\$this->store->affiliate_code}
        return 'https://' . \$this->store->domain . '/produto/' . \$slug . '?tag=' . \$this->store->affiliate_code;
    }

    public function validateSlug(\$slug)
    {
        // Implementar validação específica para {$store->name}
        if (!Security::validateSlug(\$slug)) {
            return false;
        }
        
        // Additional store-specific validation can be added here
        return !empty(\$slug);
    }
}";
    }

    public function actionDelete(ParameterBag $params)
    {
        $store = $this->assertStoreExists($params->store_id);

        if ($this->isPost())
        {
            // CSRF Protection
            if (!$this->validateCsrfToken($this->filter('_xfToken', 'str'))) {
                throw $this->exception($this->error(\XF::phrase('something_went_wrong_please_try_again')));
            }
            
            $storeName = $store->name; // Store name before deletion
            $store->delete();
            
            return $this->redirect($this->buildLink('affiliates'));
        }
        else
        {
            $viewParams = [
                'store' => $store
            ];
            return $this->view('hardMOB\Afiliados:Store\Delete', 'hardmob_afiliados_store_delete', $viewParams);
        }
    }

    /**
     * Admin dashboard with analytics
     */
    public function actionDashboard()
    {
        $analytics = $this->app()->service('hardMOB\Afiliados:Analytics');
        $storeRepo = $this->getStoreRepo();
        
        // Get overall statistics
        $totalStats = $analytics->getConversionRate(null, 'month');
        $weekStats = $analytics->getConversionRate(null, 'week');
        $dayStats = $analytics->getConversionRate(null, 'day');
        
        // Get top performing stores
        $stores = $storeRepo->findActiveStores()->fetch();
        $storeStats = [];
        
        foreach ($stores as $store) {
            $storeStats[$store->store_id] = $analytics->getConversionRate($store->store_id, 'month');
        }
        
        // Sort stores by clicks
        uasort($storeStats, function($a, $b) {
            return $b['total_clicks'] - $a['total_clicks'];
        });
        
        $viewParams = [
            'totalStats' => $totalStats,
            'weekStats' => $weekStats,
            'dayStats' => $dayStats,
            'storeStats' => array_slice($storeStats, 0, 10, true), // Top 10
            'totalStores' => count($stores),
            'stores' => $stores
        ];

        return $this->view('hardMOB\Afiliados:Dashboard', 'hardmob_afiliados_dashboard', $viewParams);
    }

    /**
     * Audit log viewer
     */
    public function actionAuditLog()
    {
        $page = $this->filterPage();
        $perPage = 50;
        
        $logFinder = $this->em()->getFinder('hardMOB\Afiliados:AuditLog')
            ->order('created_date', 'DESC')
            ->limitByPage($page, $perPage);
        
        $logs = $logFinder->fetch();
        $total = $logFinder->total();
        
        $viewParams = [
            'logs' => $logs,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total
        ];

        return $this->view('hardMOB\Afiliados:AuditLog', 'hardmob_afiliados_audit_log', $viewParams);
    }

    protected function validateCsrfToken($token)
    {
        return $token && Security::validateCsrfToken($token);
    }

    protected function assertStoreExists($id, $with = null, $phraseKey = null)
    {
        return $this->assertRecordExists('hardMOB\Afiliados:Store', $id, $with, $phraseKey);
    }

    protected function getStoreRepo()
    {
        return $this->repository('hardMOB\Afiliados:Store');
    }
}
