<?php declare(strict_types=1);

namespace Tests;

use Arcanist\Action\ActionResult;

class ActionResultTest extends \PHPUnit\Framework\TestCase
{
    /** @test */
    public function it_can_return_a_successful_result(): void
    {
        $result = ActionResult::success([]);

        $this->assertInstanceOf(ActionResult::class, $result);
        $this->assertTrue($result->successful());
    }

    /** @test */
    public function it_can_return_a_failed_result(): void
    {
        $result = ActionResult::failed();

        $this->assertInstanceOf(ActionResult::class, $result);
        $this->assertFalse($result->successful());
    }

    /** @test */
    public function it_returns_a_payload_for_a_successful_result(): void
    {
        $result = ActionResult::success([
            '::key-1::' => '::value-1::',
            '::key-2::' => '::value-2::',
        ]);

        $this->assertEquals('::value-1::', $result->get('::key-1::'));
        $this->assertEquals('::value-2::', $result->get('::key-2::'));
    }

    /** @test */
    public function it_can_pass_along_an_error_message_for_a_failed_result(): void
    {
        $result = ActionResult::failed('::message::');

        $this->assertEquals('::message::', $result->error());
    }
}
