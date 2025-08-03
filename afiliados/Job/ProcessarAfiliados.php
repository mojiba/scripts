<?php

namespace Afiliados\Job;

class ProcessarAfiliados extends \XF\Job\AbstractJob
{
    protected $defaultData = [
        'start' => 0,
        'batch' => 100,
        'total' => null
    ];

    public function run($maxRunTime)
    {
        $startTime = microtime(true);
        
        if ($this->data['total'] === null)
        {
            $this->data['total'] = $this->calculateTotal();
        }
        
        // Implementar lógica do job aqui
        
        $this->data['start'] += $this->data['batch'];
        
        if ($this->data['start'] >= $this->data['total'])
        {
            return $this->complete();
        }
        
        return $this->resume();
    }
    
    protected function calculateTotal()
    {
        // Calcular total de itens a processar
        return 100; // Exemplo
    }

    public function getStatusMessage()
    {
        $actionPhrase = \XF::phrase('processing');
        return sprintf('%s... (%d/%d)', $actionPhrase, $this->data['start'], $this->data['total']);
    }

    public function canCancel()
    {
        return true;
    }

    public function canTriggerByChoice()
    {
        return true;
    }
}