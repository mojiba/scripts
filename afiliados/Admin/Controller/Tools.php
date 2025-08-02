<?php

namespace hardMOB\Afiliados\Admin\Controller;

use XF\Admin\Controller\AbstractController;
use XF\Mvc\ParameterBag;

class Tools extends AbstractController
{
    protected function preDispatchController($action, ParameterBag $params)
    {
        $this->assertAdminPermission('hardmob_afiliados');
    }

    public function actionIndex()
    {
        $cacheService = $this->service('hardMOB\Afiliados:Cache');
        $statsService = $this->service('hardMOB\Afiliados:Stats');

        $viewParams = [
            'cacheStats' => $cacheService->getStats(),
            'clickStats' => $statsService->getOverallStats()
        ];

        return $this->view('hardMOB\Afiliados:Tools\Index', 'hardmob_afiliados_tools', $viewParams);
    }

    public function actionClearCache()
    {
        if ($this->isPost())
        {
            $cacheService = $this->service('hardMOB\Afiliados:Cache');
            $cleared = $cacheService->clearAll();

            return $this->message(\XF::phrase('hardmob_afiliados_cache_cleared', ['count' => $cleared]));
        }

        return $this->view('hardMOB\Afiliados:Tools\ClearCache', 'hardmob_afiliados_clear_cache');
    }

    public function actionResetAll()
    {
        if ($this->isPost())
        {
            $confirm = $this->filter('confirm', 'bool');
            
            if ($confirm)
            {
                $this->performCompleteReset();
                return $this->message(\XF::phrase('hardmob_afiliados_reset_complete'));
            }
        }

        return $this->view('hardMOB\Afiliados:Tools\Reset', 'hardmob_afiliados_reset');
    }

    protected function performCompleteReset()
    {
        $db = $this->app->db();
        
        // Clear all tables
        $db->emptyTable('xf_hardmob_affiliate_stores');
        $db->emptyTable('xf_hardmob_affiliate_clicks');
        $db->emptyTable('xf_hardmob_affiliate_cache');
        
        // Clear cache
        $cacheService = $this->service('hardMOB\Afiliados:Cache');
        $cacheService->clearAll();
        
        // Cancel scheduled jobs
        $db->delete('xf_job', "unique_key LIKE 'hardmobAfiliados%'");
    }

    public function actionStats()
    {
        $statsService = $this->service('hardMOB\Afiliados:Stats');
        
        $input = $this->filter([
            'period' => 'str',
            'store_id' => 'uint',
            'user_id' => 'uint'
        ]);

        $stats = $statsService->getFilteredStats($input);

        $viewParams = [
            'stats' => $stats,
            'filters' => $input,
            'stores' => $this->repository('hardMOB\Afiliados:Store')->findStoresForList()->fetch()
        ];

        return $this->view('hardMOB\Afiliados:Tools\Stats', 'hardmob_afiliados_stats', $viewParams);
    }

    public function actionErrorLogs()
    {
        // Get recent XenForo errors related to Afiliados
        $db = $this->app->db();
        
        $errors = $db->fetchAll('
            SELECT error_id, message, filename, line, trace_string, request_url, request_state, user_id, ip_address, error_date
            FROM xf_error_log
            WHERE message LIKE ?
            ORDER BY error_date DESC
            LIMIT 50
        ', ['%[Afiliados]%']);

        $viewParams = [
            'errors' => $errors,
            'errorCount' => count($errors)
        ];

        return $this->view('hardMOB\Afiliados:Tools\ErrorLogs', 'hardmob_afiliados_error_logs', $viewParams);
    }

    public function actionTestErrorHandling()
    {
        if ($this->isPost()) {
            $errorHandler = $this->service('hardMOB\Afiliados:ErrorHandler');
            
            // Test different types of errors
            $errorHandler->logError('cache_redis_connection', 'Test Redis connection error');
            $errorHandler->logError('invalid_slug', 'Test invalid slug error', ['slug' => 'test-slug-123']);
            $errorHandler->logWarning('cache_unavailable', 'Test cache unavailable warning');
            $errorHandler->logInfo('cache_fallback', 'Test cache fallback info');
            
            $errors = $errorHandler->getFormattedErrors();
            $warnings = $errorHandler->getFormattedWarnings();
            
            return $this->message('Teste de error handling concluído. Verifique os logs do sistema para ver as mensagens geradas.');
        }

        return $this->view('hardMOB\Afiliados:Tools\TestErrorHandling', 'hardmob_afiliados_test_error_handling');
    }
}
