<?php

declare(strict_types=1);

namespace App\Tests;

use App\Service\Osm\HttpClient;

/** Records requests and returns canned responses. */
final class FakeHttpClient extends HttpClient
{
    /** @var list<array{method: string, url: string, data: array<string, mixed>}> */
    public array $requests = [];

    public function __construct(private string $response)
    {
        parent::__construct('test');
    }

    public function get(string $url, array $query = []): string
    {
        $this->requests[] = ['method' => 'GET', 'url' => $url, 'data' => $query];
        return $this->response;
    }

    public function post(string $url, array $form): string
    {
        $this->requests[] = ['method' => 'POST', 'url' => $url, 'data' => $form];
        return $this->response;
    }
}
