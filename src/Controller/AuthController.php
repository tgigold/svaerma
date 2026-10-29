<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Response;

final class AuthController extends Controller
{
    /** @param array<string, string> $params */
    public function form(array $params): Response
    {
        if ($this->app->auth()->check()) {
            return $this->redirect('/');
        }
        return $this->render('auth/login', [], 200, 'layout_minimal');
    }

    /** @param array<string, string> $params */
    public function login(array $params): Response
    {
        $result = $this->app->auth()->attempt((string) ($this->request->post['password'] ?? ''), $this->request->ip);

        if ($result === 'ok') {
            $intended = $this->app->session()->get('intended', '/');
            $this->app->session()->remove('intended');
            // Only allow local paths as redirect target.
            $target = is_string($intended) && str_starts_with($intended, '/') && !str_starts_with($intended, '//') ? $intended : '/';
            return $this->redirect($target);
        }

        $error = $result === 'locked'
            ? 'Zu viele Fehlversuche. Bitte warte 15 Minuten.'
            : 'Das Passwort ist falsch.';
        return $this->render('auth/login', ['error' => $error], $result === 'locked' ? 429 : 401, 'layout_minimal');
    }

    /** @param array<string, string> $params */
    public function logout(array $params): Response
    {
        $this->app->auth()->logout();
        return $this->redirect('/login');
    }
}
