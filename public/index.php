<?php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

// XAMPP defaults to 30s max execution time, which can be too low for
// first-request cache warmup and heavier admin analytics requests in dev.
if (\function_exists('set_time_limit')) {
    @set_time_limit((int) ($_ENV['APP_MAX_EXECUTION_TIME'] ?? $_SERVER['APP_MAX_EXECUTION_TIME'] ?? 120));
}

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
