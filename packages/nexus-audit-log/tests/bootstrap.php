<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Test Bootstrap
|--------------------------------------------------------------------------
|
| This file is loaded before any tests run for the atomic audit-log package.
| It sets up the testing environment to be completely isolated from external
| dependencies, enabling true independent testability.
|
*/

// Ensure we're running in a test environment
if (! defined('LARAVEL_START')) {
    define('LARAVEL_START', microtime(true));
}

// Load Composer autoloader
require_once __DIR__.'/../vendor/autoload.php';

// Set testing environment variables
$_ENV['APP_ENV'] = 'testing';
$_ENV['DB_CONNECTION'] = 'testing';
$_ENV['CACHE_DRIVER'] = 'array';
$_ENV['SESSION_DRIVER'] = 'array';
$_ENV['QUEUE_CONNECTION'] = 'sync';
$_ENV['MAIL_MAILER'] = 'array';

// Configure error reporting for tests
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('memory_limit', '512M');