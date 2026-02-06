<?php

declare(strict_types=1);

use App\Controllers\AdminController;
use App\Controllers\ApiController;
use App\Controllers\AuthController;
use App\Controllers\KnowledgeBaseController;
use App\Controllers\ReportController;
use App\Controllers\TicketController;
use App\Controllers\UpdateController;
use App\Middleware\ApiAuthMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\LocaleMiddleware;
use App\Middleware\MaintenanceMiddleware;
use App\Middleware\RateLimitMiddleware;
use App\Middleware\RoleMiddleware;
use App\Middleware\SecurityHeadersMiddleware;
use Slim\Routing\RouteCollectorProxy;

$app->add(new SecurityHeadersMiddleware());
$app->add(new MaintenanceMiddleware(storage_path('maintenance.flag')));
$app->add(new LocaleMiddleware($app->getContainer()->get('config.app')));

$authController = new AuthController($app->getContainer());
$ticketController = new TicketController($app->getContainer());
$kbController = new KnowledgeBaseController($app->getContainer());
$reportController = new ReportController($app->getContainer());
$adminController = new AdminController($app->getContainer());
$updateController = new UpdateController($app->getContainer());
$apiController = new ApiController($app->getContainer());

$app->get('/', [$ticketController, 'dashboard']);

$app->get('/login', [$authController, 'showLogin']);
$app->post('/login', [$authController, 'login'])
    ->add(new RateLimitMiddleware(storage_path('cache/ratelimit'), 5, 60))
    ->add(new CsrfMiddleware());
$app->get('/register', [$authController, 'showRegister']);
$app->post('/register', [$authController, 'register'])->add(new CsrfMiddleware());
$app->get('/verify-email/{token}', [$authController, 'verifyEmail']);
$app->get('/logout', [$authController, 'logout']);
$app->get('/password/forgot', [$authController, 'showForgot']);
$app->post('/password/forgot', [$authController, 'sendReset'])->add(new CsrfMiddleware());
$app->get('/password/reset/{token}', [$authController, 'showReset']);
$app->post('/password/reset/{token}', [$authController, 'resetPassword'])->add(new CsrfMiddleware());

$app->group('', function (RouteCollectorProxy $group) use ($ticketController, $kbController, $reportController, $adminController, $updateController) {
    $group->get('/dashboard', [$ticketController, 'dashboard']);

    $group->get('/tickets', [$ticketController, 'index']);
    $group->get('/tickets/create', [$ticketController, 'create']);
    $group->post('/tickets', [$ticketController, 'store'])->add(new CsrfMiddleware());
    $group->get('/tickets/{id}', [$ticketController, 'view']);
    $group->post('/tickets/{id}/reply', [$ticketController, 'reply'])->add(new CsrfMiddleware());
    $group->post('/tickets/{id}/close', [$ticketController, 'close'])->add(new CsrfMiddleware());
    $group->post('/tickets/{id}/reopen', [$ticketController, 'reopen'])->add(new CsrfMiddleware());

    $group->get('/kb', [$kbController, 'index']);
    $group->get('/kb/{id}', [$kbController, 'view']);

    $group->get('/reports', [$reportController, 'index']);
    $group->get('/reports/export', [$reportController, 'exportCsv']);

    $group->group('/admin', function (RouteCollectorProxy $admin) use ($adminController, $updateController) {
        $admin->get('/settings', [$adminController, 'settings']);
        $admin->post('/settings', [$adminController, 'saveSettings'])->add(new CsrfMiddleware());

        $admin->get('/updates', [$updateController, 'index']);
        $admin->post('/updates/check', [$updateController, 'check'])->add(new CsrfMiddleware());
        $admin->post('/updates/download', [$updateController, 'download'])->add(new CsrfMiddleware());
    })->add(new RoleMiddleware('admin'));
})->add(new AuthMiddleware());

$app->get('/api/health', [$apiController, 'health']);

$app->group('/api', function (RouteCollectorProxy $group) use ($apiController) {
    $group->get('/tickets', [$apiController, 'tickets']);
    $group->get('/tickets/{id}', [$apiController, 'ticket']);
    $group->post('/tickets/{id}/reply', [$apiController, 'reply']);
})->add(new ApiAuthMiddleware($app->getContainer()));
