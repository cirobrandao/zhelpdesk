<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Dotenv\Dotenv;
use Slim\Factory\AppFactory;

require __DIR__ . '/helpers.php';

$dotenv = Dotenv::createImmutable(base_path());
$dotenv->safeLoad();

$configApp = require base_path('config/app.php');

$sessionParams = [
    'cookie_httponly' => true,
    'cookie_secure' => $configApp['session']['secure'],
    'cookie_samesite' => $configApp['session']['samesite'],
];

if (PHP_SESSION_ACTIVE !== session_status()) {
    session_start($sessionParams);
}

$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(base_path('app/container.php'));
$container = $containerBuilder->build();

AppFactory::setContainer($container);
$app = AppFactory::create();

$configApp = $container->get('config.app');
$app->addErrorMiddleware($configApp['debug'], true, true);
$app->addBodyParsingMiddleware();

require base_path('app/routes.php');

return $app;
