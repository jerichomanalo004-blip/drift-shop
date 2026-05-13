<?php
namespace Core;

class CSRF {
    public static function init(): void {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    public static function token(): string {
        self::init();
        return $_SESSION['csrf_token'];
    }

    public static function validate(?string $token): bool {
        self::init();
        return is_string($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }
}
