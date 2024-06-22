<?php

namespace Jennchen\Duckpatrol\Middleware;

class DuckPatrol
{
    /**
     * Initializes the session if it is not already started.
     *
     * @return void
     */
    public static function initialize()
    {
        if (!isset($_SESSION)) {
            session_start();
        }
    }

    /**
     * Creates a CSRF token and stores it in the session.
     *
     * @return void
     * @throws \Exception
     */
    public static function createAndStoreToken()
    {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    /**
     * Validates the CSRF token from the form against the one stored in the session.
     *
     * @return bool Returns true if the token is valid, false otherwise.
     */
    public static function isValidToken(): bool
    {
        return (isset($_POST['csrf_token']) && $_SESSION['csrf_token'] == $_POST['csrf_token']);
    }

    /**
     * Returns an HTML input element containing the CSRF token.
     *
     * @return string The HTML input element with the CSRF token.
     */
    public static function getCsrfInput(): string
    {
        return "<input type='hidden' name='csrf_token' value='" . htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') . "' />";
    }
}
