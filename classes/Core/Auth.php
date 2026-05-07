<?php
namespace Core;

use Models\User;

class Auth {
    public static function attempt($email, $password) {
        $user = new User();
        return $user->login($email, $password);
    }
    public static function user() {
        if (!SessionManager::has('user_id')) return null;
        $user = new User();
        return $user->find(SessionManager::get('user_id'));
    }
    public static function check() { return SessionManager::has('user_id'); }
    public static function isAdmin() { return SessionManager::get('role') === 'admin'; }
    public static function logout() { SessionManager::destroy(); }
}