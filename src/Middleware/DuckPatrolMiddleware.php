<?php

namespace Jennchen\Duckpatrol\Middleware;

require_once __DIR__ . '\..\config.php';
require_once 'DuckPatrol.php';

class DuckPatrolMiddleware
{
    /**
     * Handles the incoming request and applies CSRF protection.
     *
     * @return void
     */
    public static function handle()
    {
        global $_CONF;
        DuckPatrol::initialize();

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // For GET requests, create and store a new CSRF token and start output buffering
            DuckPatrol::createAndStoreToken();
            ob_start([__CLASS__, 'modifyForm']);
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // For POST requests, validate the CSRF token and if needed create and store a new token
            if (DuckPatrol::isValidToken()) {
                DuckPatrol::createAndStoreToken();
                ob_start([__CLASS__, 'modifyForm']);
            } else {
                // If invalid, destroy the session and redirect to the configured landing page in config.php
                session_destroy();
                $landingPage = $_CONF['landingPage'] ?? '';
                header("Location: " . $landingPage);
                exit();
            }
        }
    }

    /**
     * Modifies the form by adding a CSRF token input field.
     * This method is used as a callback for the output buffering.
     *
     * @param string $html The HTML content to modify.
     * @return array|string|string[]|null The modified HTML content.
     */
    private static function modifyForm(string $html)
    {
        // Use a regex to find form tags with the method 'post'
        return preg_replace_callback(
            '/<form\b[^>]*\bmethod=["\']?post["\']?[^>]*>/i',
            [__CLASS__, 'addTokenToForm'],
            $html
        );
    }

    /**
     * Adds the CSRF token input field to the form.
     * This method is called by the regex callback in modifyForm.
     *
     * @param array $matches The regex matches.
     * @return string The form tag with the added CSRF token input field.
     */
    private static function addTokenToForm($matches): string
    {
        $csrfInput = DuckPatrol::getCsrfInput();
        return $matches[0] . $csrfInput;
    }
}
