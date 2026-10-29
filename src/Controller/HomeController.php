<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Response;

final class HomeController extends Controller
{
    /** @param array<string, string> $params */
    public function index(array $params): Response
    {
        return $this->render('home');
    }
}
