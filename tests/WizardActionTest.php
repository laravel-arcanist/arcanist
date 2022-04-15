<?php

declare(strict_types=1);

/**
 * Copyright (c) 2022 Kai Sassnowski
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/laravel-arcanist/arcanist
 */

namespace Arcanist\Tests;

use Arcanist\Action\ActionResult;
use Arcanist\Action\WizardAction;

class WizardActionTest extends \PHPUnit\Framework\TestCase
{
    public function testItCanReturnASuccessfulResult(): void
    {
        $action = new class() extends WizardAction {
            public function execute(mixed $payload = null): ActionResult
            {
                return $this->success();
            }
        };

        $result = $action->execute();

        self::assertTrue($result->successful());
    }

    public function testItPassesAlongThePayload(): void
    {
        $action = new class() extends WizardAction {
            public function execute(mixed $payload = null): ActionResult
            {
                return $this->success(['::key::' => '::value::']);
            }
        };

        $result = $action->execute();

        self::assertEquals('::value::', $result->get('::key::'));
    }

    public function testItCanReturnAFailedResult(): void
    {
        $action = new class() extends WizardAction {
            public function execute(mixed $payload = null): ActionResult
            {
                return $this->failure();
            }
        };

        $result = $action->execute();

        self::assertFalse($result->successful());
    }

    public function testItPassesAlongTheErrorMessageForAFailedResult(): void
    {
        $action = new class() extends WizardAction {
            public function execute(mixed $payload = null): ActionResult
            {
                return $this->failure('::error::');
            }
        };

        $result = $action->execute();

        self::assertEquals('::error::', $result->error());
    }
}
