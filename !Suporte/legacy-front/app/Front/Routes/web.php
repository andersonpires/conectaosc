<?php
declare(strict_types=1);

use Front\Controllers\SystemController;

$systemController = new SystemController();
$router->get('/_front/health', [$systemController, 'health']);

