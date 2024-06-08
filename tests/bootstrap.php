<?php

declare(strict_types=1);

use Symfony\Component\ErrorHandler\ErrorHandler;

if (is_dir('/app')) {
    require '/app/vendor/autoload.php';
} else {
    require '/workspace/vendor/autoload.php';
}

set_exception_handler([new ErrorHandler(), 'handleException']);
