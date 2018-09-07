<?php
declare(strict_types=1);

chdir(__DIR__);

require 'vendor/autoload.php';

use Firehed\API\ErrorHandler;
use Firehed\SimpleLogger\Stdout;

// ini_set('error_log', 'stderr');
// ini_set('display_errors', 'off');
// ini_set('error_reporting', 'E_ALL');
// ini_set('expose_php', 'false');
// ini_set('log_errors', 'true');

$handler = new ErrorHandler(new Stdout());
set_error_handler([$handler, 'handleError'], -1);
set_exception_handler([$handler, 'handleThrowable']);
