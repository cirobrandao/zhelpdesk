<?php

declare(strict_types=1);

use App\Repositories\AuditRepository;
use App\Repositories\KnowledgeBaseRepository;
use App\Repositories\SettingsRepository;
use App\Repositories\TicketRepository;
use App\Repositories\UpdateRepository;
use App\Repositories\UserRepository;
use App\Services\AuditService;
use App\Services\AuthService;
use App\Services\KnowledgeBaseService;
use App\Services\ReportService;
use App\Services\TicketService;
use App\Services\UpdateService;
use App\Services\UserService;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PDO;
use Slim\Views\Twig;
use Twig\TwigFunction;

return [
    'config.app' => fn () => require base_path('config/app.php'),
    'config.db' => fn () => require base_path('config/database.php'),
    'config.updater' => fn () => require base_path('config/updater.php'),

    Logger::class => function (): Logger {
        $logDir = storage_path('logs');
        if (!is_dir($logDir)) {
            mkdir($logDir, 0775, true);
        }
        $logger = new Logger('app');
        $logger->pushHandler(new StreamHandler($logDir . DIRECTORY_SEPARATOR . 'app.log'));
        return $logger;
    },

    PDO::class => function () {
        $db = require base_path('config/database.php');
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $db['host'],
            $db['port'],
            $db['name'],
            $db['charset']
        );
        $pdo = new PDO($dsn, $db['user'], $db['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    },

    Twig::class => function ($container): Twig {
        $twig = Twig::create(base_path('app/views'), [
            'cache' => storage_path('cache/twig'),
            'auto_reload' => true,
        ]);
        $twig->getEnvironment()->addFunction(new TwigFunction('t', function (string $key, array $params = []) use ($container) {
            $locale = $_SESSION['locale'] ?? null;
            return t($key, $params, $locale);
        }));
        $twig->getEnvironment()->addFunction(new TwigFunction('csrf_token', function (): string {
            return csrf_token();
        }));
        $twig->getEnvironment()->addGlobal('app', $container->get('config.app'));
        $twig->getEnvironment()->addGlobal('agents', $container->get(\App\Services\UserService::class)->listAgents());
        return $twig;
    },

    UserRepository::class => fn ($c) => new UserRepository($c->get(PDO::class)),
    TicketRepository::class => fn ($c) => new TicketRepository($c->get(PDO::class)),
    KnowledgeBaseRepository::class => fn ($c) => new KnowledgeBaseRepository($c->get(PDO::class)),
    SettingsRepository::class => fn ($c) => new SettingsRepository($c->get(PDO::class)),
    UpdateRepository::class => fn ($c) => new UpdateRepository($c->get(PDO::class)),
    AuditRepository::class => fn ($c) => new AuditRepository($c->get(PDO::class)),

    AuthService::class => fn ($c) => new AuthService($c->get(UserRepository::class), $c->get(AuditService::class), $c->get('config.app')),
    TicketService::class => fn ($c) => new TicketService($c->get(TicketRepository::class), $c->get(AuditService::class)),
    KnowledgeBaseService::class => fn ($c) => new KnowledgeBaseService($c->get(KnowledgeBaseRepository::class)),
    ReportService::class => fn ($c) => new ReportService($c->get(TicketRepository::class)),
    UpdateService::class => fn ($c) => new UpdateService($c->get(UpdateRepository::class), $c->get('config.updater')),
    UserService::class => fn ($c) => new UserService($c->get(UserRepository::class)),
    AuditService::class => fn ($c) => new AuditService($c->get(AuditRepository::class)),
];
