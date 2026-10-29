<?php

declare(strict_types=1);

namespace App\Model;

/**
 * What visitors without login may see (public mode). Logged-in requests use no
 * Visibility at all, i.e. everything is visible.
 */
final class Visibility
{
    public function __construct(
        /** Only the newest N check-ins (after the other filters); null = all */
        public readonly ?int $limit,
        /** Check-ins become public only this many hours after they happened */
        public readonly int $delayHours,
        public readonly bool $showNotes,
        /** Include manually named places (without OSM reference) */
        public readonly bool $showManual,
        private readonly ?\DateTimeImmutable $now = null,
    ) {
    }

    /** Latest check-in time (UTC, DB format) that is already public. */
    public function cutoff(): string
    {
        $now = $this->now ?? new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        return $now->setTimezone(new \DateTimeZone('UTC'))
            ->modify(sprintf('-%d hours', $this->delayHours))
            ->format('Y-m-d H:i:s');
    }
}
