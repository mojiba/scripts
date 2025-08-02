<?php

namespace hardMOB\Afiliados\Service;

use XF\Service\AbstractService;

class ErrorHandler extends AbstractService
{
    protected $errors = [];
    protected $warnings = [];
    protected $info = [];

    public function logError($type, $message, array $params = [], \Exception $exception = null)
    {
        $phraseKey = 'hardmob_afiliados_error_' . $type;
        $errorMessage = $this->app->phrase($phraseKey, $params)->render('raw');
        
        $this->errors[] = [
            'type' => $type,
            'message' => $errorMessage,
            'raw_message' => $message,
            'params' => $params,
            'exception' => $exception,
            'timestamp' => \XF::$time
        ];

        // Log to XenForo error log for admin tracking
        if ($exception) {
            \XF::logException($exception, false, "[Afiliados] {$errorMessage}: ");
        } else {
            \XF::logError("[Afiliados] {$errorMessage}");
        }

        return $errorMessage;
    }

    public function logWarning($type, $message, array $params = [])
    {
        $phraseKey = 'hardmob_afiliados_warning_' . $type;
        $warningMessage = $this->app->phrase($phraseKey, $params)->render('raw');
        
        $this->warnings[] = [
            'type' => $type,
            'message' => $warningMessage,
            'raw_message' => $message,
            'params' => $params,
            'timestamp' => \XF::$time
        ];

        return $warningMessage;
    }

    public function logInfo($type, $message, array $params = [])
    {
        $phraseKey = 'hardmob_afiliados_info_' . $type;
        $infoMessage = $this->app->phrase($phraseKey, $params)->render('raw');
        
        $this->info[] = [
            'type' => $type,
            'message' => $infoMessage,
            'raw_message' => $message,
            'params' => $params,
            'timestamp' => \XF::$time
        ];

        return $infoMessage;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getWarnings()
    {
        return $this->warnings;
    }

    public function getInfo()
    {
        return $this->info;
    }

    public function hasErrors()
    {
        return !empty($this->errors);
    }

    public function hasWarnings()
    {
        return !empty($this->warnings);
    }

    public function getLastError()
    {
        return end($this->errors);
    }

    public function getLastWarning()
    {
        return end($this->warnings);
    }

    public function clearErrors()
    {
        $this->errors = [];
    }

    public function clearWarnings()
    {
        $this->warnings = [];
    }

    public function clearInfo()
    {
        $this->info = [];
    }

    public function clearAll()
    {
        $this->clearErrors();
        $this->clearWarnings();
        $this->clearInfo();
    }

    /**
     * Format error for display to end users
     */
    public function getFormattedErrors()
    {
        $formatted = [];
        foreach ($this->errors as $error) {
            $formatted[] = $error['message'];
        }
        return $formatted;
    }

    /**
     * Format warnings for display to end users
     */
    public function getFormattedWarnings()
    {
        $formatted = [];
        foreach ($this->warnings as $warning) {
            $formatted[] = $warning['message'];
        }
        return $formatted;
    }

    /**
     * Get summary for admin dashboard
     */
    public function getErrorSummary()
    {
        return [
            'error_count' => count($this->errors),
            'warning_count' => count($this->warnings),
            'info_count' => count($this->info),
            'last_error' => $this->getLastError(),
            'last_warning' => $this->getLastWarning()
        ];
    }
}