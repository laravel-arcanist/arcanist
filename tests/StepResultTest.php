<?php declare(strict_types=1);

namespace Tests;

use Arcanist\StepResult;
use PHPUnit\Framework\TestCase;

class StepResultTest extends TestCase
{
    /** @test */
    public function can_create_a_successful_result(): void
    {
        $result = StepResult::success();

        self::assertTrue($result->successful());
    }

    /** @test */
    public function can_create_a_failed_result(): void
    {
        $result = StepResult::failed();

        self::assertFalse($result->successful());
    }

    /** @test */
    public function can_pass_along_data_to_successful_result(): void
    {
        $result = StepResult::success(['::data::']);

        self::assertEquals(['::data::'], $result->payload());
    }

    /** @test */
    public function can_pass_along_error_message_to_failed_result(): void
    {
        $result = StepResult::failed('::message::');

        self::assertEquals('::message::', $result->error());
    }
}
