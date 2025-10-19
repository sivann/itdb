<?php

declare(strict_types=1);

use DI\Container;

require_once __DIR__ . '/../vendor/autoload.php';

// Set timezone
date_default_timezone_set('UTC');

// Define test environment
define('APP_ENV', 'testing');

// Load environment variables for testing
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// Override environment variables for testing
$_ENV['APP_ENV'] = 'testing';
$_ENV['DB_PATH'] = ':memory:'; // Use in-memory SQLite for tests
