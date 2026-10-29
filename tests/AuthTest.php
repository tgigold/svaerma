<?php

declare(strict_types=1);

namespace App\Tests;

use App\Http\Session;
use App\Repository\LoginAttemptRepository;
use App\Repository\SettingsRepository;
use App\Service\Auth;

final class AuthTest extends TestCase
{
    private function auth(): Auth
    {
        $pdo = $this->migratedPdo();
        $settings = new SettingsRepository($pdo);
        $settings->set('password_hash', password_hash('correct horse', PASSWORD_DEFAULT));
        $_SESSION = [];
        return new Auth(new Session($this->tempDir(), true), $settings, new LoginAttemptRepository($pdo));
    }

    public function testLoginWithCorrectPassword(): void
    {
        $auth = $this->auth();
        self::assertFalse($auth->check());
        self::assertSame('ok', $auth->attempt('correct horse', '1.2.3.4'));
        self::assertTrue($auth->check());
    }

    public function testWrongPasswordIsRejected(): void
    {
        $auth = $this->auth();
        self::assertSame('invalid', $auth->attempt('nope', '1.2.3.4'));
        self::assertFalse($auth->check());
    }

    public function testLocksIpAfterTooManyFailures(): void
    {
        $auth = $this->auth();
        $now = 1_000_000;
        for ($i = 0; $i < Auth::MAX_PER_IP; $i++) {
            self::assertSame('invalid', $auth->attempt('nope', '1.2.3.4', $now));
        }
        // Even the right password is refused while locked.
        self::assertSame('locked', $auth->attempt('correct horse', '1.2.3.4', $now));
        // Another IP is not affected.
        self::assertSame('ok', $auth->attempt('correct horse', '5.6.7.8', $now));
        // After the window the lock is lifted.
        self::assertSame('ok', $auth->attempt('correct horse', '1.2.3.4', $now + Auth::WINDOW + 1));
    }
}
