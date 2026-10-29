<?php

declare(strict_types=1);

namespace App\Service\Osm;

/**
 * Process-safe rate limit (e.g. Nominatim: max. 1 request per second) via a lock file.
 */
final class Throttle
{
    public function __construct(
        private string $file,
        private float $interval,
    ) {
    }

    public function wait(): void
    {
        $handle = fopen($this->file, 'c+');
        if ($handle === false) {
            usleep((int) ($this->interval * 1_000_000));
            return;
        }
        try {
            flock($handle, LOCK_EX);
            $last = (float) stream_get_contents($handle);
            $delay = $last + $this->interval - microtime(true);
            if ($delay > 0) {
                usleep((int) ceil($delay * 1_000_000));
            }
            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, (string) microtime(true));
            fflush($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
