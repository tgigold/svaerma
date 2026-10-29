<?php

declare(strict_types=1);

use App\Controller\AdminController;
use App\Controller\AuthController;
use App\Controller\CheckinController;
use App\Controller\PlaceController;
use App\Controller\SettingsController;
use App\Controller\SetupController;
use App\Http\Router;

/*
 * All routes. Options:
 *   public            => true  no login required
 *   setup             => true  only reachable before setup is complete
 *   api               => true  token auth instead of session/CSRF (see API controllers)
 *   ignore_migrations => true  reachable while migrations are pending
 */
return static function (Router $r): void {
    $r->get('/setup', [SetupController::class, 'form'], ['setup' => true]);
    $r->post('/setup', [SetupController::class, 'submit'], ['setup' => true]);

    $r->get('/login', [AuthController::class, 'form'], ['public' => true]);
    $r->post('/login', [AuthController::class, 'login'], ['public' => true]);
    $r->post('/logout', [AuthController::class, 'logout'], ['ignore_migrations' => true]);

    $r->get('/', [CheckinController::class, 'create']);
    $r->post('/checkin', [CheckinController::class, 'store']);
    $r->get('/places/nearby', [PlaceController::class, 'nearby']);
    $r->get('/places/search', [PlaceController::class, 'search']);

    $r->get('/settings', [SettingsController::class, 'index']);
    $r->post('/settings', [SettingsController::class, 'save']);

    $r->get('/admin', [AdminController::class, 'index'], ['ignore_migrations' => true]);
    $r->post('/admin/migrate', [AdminController::class, 'migrate'], ['ignore_migrations' => true]);
    $r->post('/admin/backup', [AdminController::class, 'backup'], ['ignore_migrations' => true]);
};
