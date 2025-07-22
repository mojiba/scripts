<?php
function getOPcacheStatus() {
    if (function_exists('opcache_get_status')) {
        $status = @opcache_get_status(false);
        return $status ?: [];
    }
    return [];
}