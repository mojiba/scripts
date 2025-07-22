<?php
// Carregando variáveis de ambiente (opcional)
if (file_exists(__DIR__ . '/.env')) {
    foreach (file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

// Credenciais do painel (use .env ou defina diretamente aqui)
define('MONITOR_USER', getenv('MONITOR_USER') ?: 'kingMOB');
define('MONITOR_PASS', getenv('MONITOR_PASS') ?: 'Cudemerda52!');

$config = [
    'servers' => [['host' => '127.0.0.1', 'port' => 11211, 'name' => 'Servidor Local']],
    'prefix_filters' => [
        'hmvbmc_' => 'vBulletin Core',
        'vbomc_' => 'vBOptimize Plugin'
    ],
    'default_prefix' => 'hmvbmc_',
    'items_limit' => 100,
    'memcache_server' => '127.0.0.1',
    'memcache_port' => 11211,
    'opcache_base_path' => $_SERVER['DOCUMENT_ROOT'],
    'title' => 'Monitor de Caches - Hardmob'
];