<?php

namespace Jennchen\Duckpatrol\Middleware;

class DuckPatrol
{
    /**
     * @return void
     */
    public static function initialize()
    {
        if (!isset($_SESSION)) {
            session_start();
        }
    }

    /**
     * @return void
     * @throws \Exception
     */
    public static function createAndStoreToken()
    {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }


    /**
     * @return bool
     */
    public static function isValidToken(): bool
    {
        // CSRF-Token überprüfen
        return (isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']));
    }

    /**
     * @return string
     */
    public static function getCsrfInput(): string
    {
        return "<input type='hidden' name='csrf_token' value='" . htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') . "' />";
    }
}
