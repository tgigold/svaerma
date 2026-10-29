<?php

declare(strict_types=1);

namespace App\Tests;

use App\Service\Osm\HttpClient;

/** Records requests and returns canned responses. */
final class FakeHttpClient extends HttpClient
{
    /** @var list<array{method: string, url: string, data: array<string, mixed>}> */
    public array $requests = [];

    /** @var \Closure(string): string */
    private \Closure $responder;

    /**
     * @param string|callable(string): string $response fixed body, or a function of the URL (may throw)
     */
    public function __construct(string|callable $response)
    {
        parent::__construct('test');
        $this->responder = is_string($response)
            ? static fn (string $url): string => $response
            : \Closure::fromCallable($response);
    }

    public function get(string $url, array $query = []): string
    {
        $this->requests[] = ['method' => 'GET', 'url' => $url, 'data' => $query];
        return ($this->responder)($url);
    }

    public function post(string $url, array $form): string
    {
        $this->requests[] = ['method' => 'POST', 'url' => $url, 'data' => $form];
        return ($this->responder)($url);
    }
}
