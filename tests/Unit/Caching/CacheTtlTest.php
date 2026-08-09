<?php

namespace Tests\Unit\Caching;

use App\Modules\Shared\Application\Caching\Data\CacheTtl;
use InvalidArgumentException;
use Tests\TestCase;

class CacheTtlTest extends TestCase
{
    public function test_it_exposes_durations_for_stale_while_revalidate(): void
    {
        $ttl = new CacheTtl(freshSeconds: 60, staleSeconds: 300);

        $this->assertSame([60, 300], $ttl->toFlexibleDurations());
    }

    public function test_it_requires_a_positive_fresh_duration(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new CacheTtl(freshSeconds: 0, staleSeconds: 60);
    }

    public function test_it_requires_stale_duration_to_include_the_fresh_window(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new CacheTtl(freshSeconds: 61, staleSeconds: 60);
    }
}
