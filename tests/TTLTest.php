<?php declare(strict_types=1);

namespace Tests;

use Generator;
use Carbon\Carbon;
use Sassnowski\Arcanist\TTL;
use InvalidArgumentException;

class TTLTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     * @dataProvider validValueProvider
     */
    public function it_can_be_turned_into_a_date(int $value, callable $expectedDate): void
    {
        Carbon::setTestNow(now());

        $ttl = TTL::fromSeconds($value);

        self::assertTrue($ttl->expiresAfter()->eq($expectedDate()));
    }

    public function validValueProvider(): Generator
    {
        yield from [
            [0, fn () => now()],
            [24 * 60 * 60, fn () => now()->subDay()],
            [60, fn () => now()->subMinute()],
        ];
    }

    /** @test */
    public function it_cannot_be_negative(): void
    {
        $this->expectException(InvalidArgumentException::class);

        TTL::fromSeconds(-1);
    }
}
