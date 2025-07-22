<?php
// session_start();
require_once __DIR__ . '/config.php';

function isAuthenticated() {
    return isset($_SESSION['monitor_auth']) && $_SESSION['monitor_auth'] === true;
}

function tryLogin($username, $password) {
    if (hash_equals(MONITOR_USER, $username) && hash_equals(MONITOR_PASS, $password)) {
        $_SESSION['monitor_auth'] = true;
        return true;
    }
    return false;
}

function logout() {
    session_destroy();
    header("Location: index.php");
    exit;
}