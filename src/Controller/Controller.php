<?php

declare(strict_types=1);

namespace App\Controller;

use App\App;
use App\Http\Request;
use App\Http\Response;
use App\Model\Visibility;

abstract class Controller
{
    public function __construct(
        protected App $app,
        protected Request $request,
    ) {
    }

    /** @param array<string, mixed> $data */
    protected function render(string $template, array $data = [], int $status = 200, ?string $layout = 'layout'): Response
    {
        $data['flash'] ??= $this->app->session()->takeFlash();
        return Response::html($this->app->view()->render($template, $data, $layout), $status);
    }

    protected function redirect(string $location, int $status = 303): Response
    {
        return Response::redirect($location, $status);
    }

    protected function flash(string $type, string $message): void
    {
        $this->app->session()->flash($type, $message);
    }

    /**
     * Restrictions for the current viewer: null when logged in (sees everything),
     * otherwise the public-mode rules.
     */
    protected function visibility(): ?Visibility
    {
        return $this->app->auth()->check() ? null : $this->app->publicVisibility();
    }

    protected function notFound(): Response
    {
        return $this->app->error($this->request, 404);
    }
}
