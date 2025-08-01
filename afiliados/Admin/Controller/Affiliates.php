<?php

namespace hardMOB\Afiliados\Admin\Controller;

use XF\Admin\Controller\AbstractController;
use XF\Mvc\ParameterBag;

class Affiliates extends AbstractController
{
    protected function preDispatchController($action, ParameterBag $params)
    {
        $this->assertAdminPermission('hardmob_afiliados');
    }

    public function actionIndex()
    {
        $storeRepo = $this->getStoreRepo();
        $stores = $storeRepo->findStoresForList()->fetch();

        $viewParams = [
            'stores' => $stores
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
            'store' => $store
        ];

        return $this->view('hardMOB\Afiliados:Store\Edit', 'hardmob_afiliados_store_edit', $viewParams);
    }

    public function actionSave(ParameterBag $params)
    {
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
            file_put_contents($connectorPath, $template);
        }
    }

    protected function getConnectorTemplate($className, \hardMOB\Afiliados\Entity\Store $store)
    {
        return "<?php

namespace hardMOB\\Afiliados\\Connector;

class {$className} implements StoreInterface
{
    protected \$store;

    public function __construct(\\hardMOB\\Afiliados\\Entity\\Store \$store)
    {
        \$this->store = \$store;
    }

    public function generateAffiliateUrl(\$slug)
    {
        // Implementar lógica específica para {$store->name}
        // Exemplo: https://{$store->domain}/produto/\$slug?tag={\$this->store->affiliate_code}
        return 'https://' . \$this->store->domain . '/produto/' . \$slug . '?tag=' . \$this->store->affiliate_code;
    }

    public function validateSlug(\$slug)
    {
        // Implementar validação específica para {$store->name}
        return !empty(\$slug);
    }
}";
    }

    public function actionDelete(ParameterBag $params)
    {
        $store = $this->assertStoreExists($params->store_id);

        if ($this->isPost())
        {
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

    protected function assertStoreExists($id, $with = null, $phraseKey = null)
    {
        return $this->assertRecordExists('hardMOB\Afiliados:Store', $id, $with, $phraseKey);
    }

    protected function getStoreRepo()
    {
        return $this->repository('hardMOB\Afiliados:Store');
    }
}
