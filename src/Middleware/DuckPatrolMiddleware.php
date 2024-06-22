<?php

namespace duckpatrol\src\Middleware;

require_once __DIR__ . '\..\config.php';
require_once 'DuckPatrol.php';

class DuckPatrolMiddleware
{
    /**
     * @return void
     */
    public static function handle()
    {
        global $_CONF;
        DuckPatrol::initialize();
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            DuckPatrol::createAndStoreToken(); //
            ob_start([__CLASS__, 'modifyForm']);
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (DuckPatrol::isValidToken()) {
                DuckPatrol::createAndStoreToken();
                ob_start([__CLASS__, 'modifyForm']);
            } else {
                session_destroy();
                $landingPage = $_CONF['landingPage'] ?? '';
                header("Location: " . $landingPage);
                exit();
            }
        }
    }

    /**
     * @param string $html
     * @return array|string|string[]|null
     */
    private static function modifyForm(string $html)
    {
        return preg_replace_callback(
            '/<form\b[^>]*\bmethod=["\']?post["\']?[^>]*>/i',
            [__CLASS__, 'addTokenToForm'],
            $html
        );
    }

    /**
     * @param $matches
     * @return string
     */
    private static function addTokenToForm($matches): string
    {
        $csrfInput = DuckPatrol::getCsrfInput();
        return $matches[0] . $csrfInput;
    }
}
