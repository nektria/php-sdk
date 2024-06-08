<?php

declare(strict_types=1);

use Symfony\Component\ErrorHandler\ErrorHandler;

require './vendor/autoload.php';

set_exception_handler([new ErrorHandler(), 'handleException']);
