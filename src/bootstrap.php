<?php
/** Loaded first by index.php and install.php. */

declare(strict_types=1);

if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

require __DIR__ . '/helpers.php';
require __DIR__ . '/db.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/repo.php';
require __DIR__ . '/upload.php';
require __DIR__ . '/view.php';

if (config('debug')) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    ini_set('display_errors', '0');
}

date_default_timezone_set('UTC');
