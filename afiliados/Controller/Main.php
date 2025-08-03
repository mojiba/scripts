<?php

namespace Afiliados\Controller;

use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\View;

class Main extends \XF\Pub\Controller\AbstractController
{
    public function actionIndex()
    {
        return $this->view('Afiliados:Main', 'afiliados_index');
    }
    
    // Outros métodos de controller conforme necessário
}