<?php
namespace Core;

class SessionManager {
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', '1');
            ini_set('session.use_strict_mode', '1');
            ini_set('session.cookie_samesite', 'Strict');

            if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') === '443') {
                ini_set('session.cookie_secure', '1');
            }

            session_start();
        }

        if (class_exists(CSRF::class)) {
            CSRF::init();
        }
    }
    public static function get($key, $default = null) { return $_SESSION[$key] ?? $default; }
    public static function set($key, $value) { $_SESSION[$key] = $value; }
    public static function has($key) { return isset($_SESSION[$key]); }
    public static function remove($key) { unset($_SESSION[$key]); }
    public static function destroy() { session_destroy(); }
}