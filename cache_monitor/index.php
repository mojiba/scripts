<?php
// Exibir erros em ambiente de desenvolvimento
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Carrega configurações e módulos essenciais
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/memcache_utils.php';
require_once __DIR__ . '/opcache_utils.php';
require_once __DIR__ . '/actions.php';

// Inicia sessão e verifica autenticação
session_start();
$logged_in = isAuthenticated();

// Seleção de prefixo
if (isset($_GET['prefix']) && isset($config['prefix_filters'][$_GET['prefix']])) {
    setcookie('monitor_prefix', $_GET['prefix'], time()+30*86400, '/');
    $current_prefix = $_GET['prefix'];
} elseif (isset($_COOKIE['monitor_prefix']) && isset($config['prefix_filters'][$_COOKIE['monitor_prefix']])) {
    $current_prefix = $_COOKIE['monitor_prefix'];
} else {
    $current_prefix = $config['default_prefix'];
}

// Inicializa Memcache
$mem = new Memcache();
if (isset($config['servers']) && is_array($config['servers'])) {
    foreach ($config['servers'] as $srv) {
        @$mem->connect($srv['host'], $srv['port']);
    }
}

// Logout
if (isset($_GET['logout'])) {
    logout();
}

// Tentativa de login
$login_error = '';
if (!$logged_in && isset($_POST['login'])) {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';
    if (!tryLogin($user, $pass)) {
        $login_error = 'Usuário ou senha inválidos!';
    } else {
        header('Location: index.php');
        exit;
    }
}

// Gera token CSRF
$csrf_token = generate_csrf_token();

// Carrega interface
require __DIR__ . '/templates/header.php';
if (!$logged_in) {
    require __DIR__ . '/templates/login.php';
} else {
    global $mem, $config, $csrf_token, $current_prefix;
    require __DIR__ . '/templates/dashboard.php';
}
require __DIR__ . '/templates/footer.php';