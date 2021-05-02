<?php declare(strict_types=1);

namespace Arcanist\Tests;

use Arcanist\Action\ActionResult;
use Arcanist\Action\WizardAction;

class WizardActionTest extends \PHPUnit\Framework\TestCase
{
    /** @test */
    public function it_can_return_a_successful_result(): void
    {
        $action = new class extends WizardAction {
            public function execute(mixed $payload = null): ActionResult
            {
                return $this->success();
            }
        };

        $result = $action->execute();

        $this->assertTrue($result->successful());
    }

    /** @test */
    public function it_passes_along_the_payload(): void
    {
        $action = new class extends WizardAction {
            public function execute(mixed $payload = null): ActionResult
            {
                return $this->success(['::key::' => '::value::']);
            }
        };

        $result = $action->execute();

        $this->assertEquals('::value::', $result->get('::key::'));
    }

    /** @test */
    public function it_can_return_a_failed_result(): void
    {
        $action = new class extends WizardAction {
            public function execute(mixed $payload = null): ActionResult
            {
                return $this->failure();
            }
        };

        $result = $action->execute();

        $this->assertFalse($result->successful());
    }

    /** @test */
    public function it_passes_along_the_error_message_for_a_failed_result(): void
    {
        $action = new class extends WizardAction {
            public function execute(mixed $payload = null): ActionResult
            {
                return $this->failure('::error::');
            }
        };

        $result = $action->execute();

        $this->assertEquals('::error::', $result->error());
    }
}
