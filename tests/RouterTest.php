<?php

declare(strict_types=1);

namespace App\Tests;

use App\Http\Router;

final class RouterTest extends TestCase
{
    private function router(): Router
    {
        $r = new Router();
        $r->get('/', ['Home', 'index']);
        $r->get('/checkin/{id:\d+}{slug:(?:-[^/]*)?}', ['Checkin', 'show']);
        $r->post('/checkin/{id:\d+}/delete', ['Checkin', 'delete']);
        $r->get('/export.json', ['Export', 'json']);
        $r->get('/user/{name}', ['User', 'show'], ['public' => true]);
        return $r;
    }

    public function testMatchesStaticRoute(): void
    {
        $m = $this->router()->match('GET', '/');
        self::assertNotNull($m);
        self::assertSame(['Home', 'index'], $m['route']->handler);
    }

    public function testExtractsIdAndSlug(): void
    {
        $m = $this->router()->match('GET', '/checkin/123-cafe-xyz');
        self::assertNotNull($m);
        self::assertSame('123', $m['params']['id']);
        self::assertSame('-cafe-xyz', $m['params']['slug']);

        $m = $this->router()->match('GET', '/checkin/123');
        self::assertNotNull($m);
        self::assertSame('', $m['params']['slug']);
    }

    public function testRejectsNonNumericId(): void
    {
        self::assertNull($this->router()->match('GET', '/checkin/abc'));
        self::assertNull($this->router()->match('GET', '/checkin/12/extra'));
    }

    public function testLiteralDotIsNotAWildcard(): void
    {
        self::assertNotNull($this->router()->match('GET', '/export.json'));
        self::assertNull($this->router()->match('GET', '/exportXjson'));
    }

    public function testDefaultPlaceholderStopsAtSlash(): void
    {
        self::assertSame('jo', $this->router()->match('GET', '/user/jo')['params']['name'] ?? null);
        self::assertNull($this->router()->match('GET', '/user/jo/x'));
    }

    public function testMethodMatchingAndHead(): void
    {
        $r = $this->router();
        self::assertNull($r->match('POST', '/'));
        self::assertNotNull($r->match('HEAD', '/'));
        self::assertSame(['POST'], $r->allowedMethods('/checkin/5/delete'));
    }

    public function testRouteOptions(): void
    {
        $m = $this->router()->match('GET', '/user/x');
        self::assertTrue($m['route']->option('public'));
        self::assertNull($m['route']->option('api'));
    }
}
