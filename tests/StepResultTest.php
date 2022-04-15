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

use Arcanist\StepResult;
use PHPUnit\Framework\TestCase;

class StepResultTest extends TestCase
{
    public function testCanCreateASuccessfulResult(): void
    {
        $result = StepResult::success();

        self::assertTrue($result->successful());
    }

    public function testCanCreateAFailedResult(): void
    {
        $result = StepResult::failed();

        self::assertFalse($result->successful());
    }

    public function testCanPassAlongDataToSuccessfulResult(): void
    {
        $result = StepResult::success(['::data::']);

        self::assertEquals(['::data::'], $result->payload());
    }

    public function testCanPassAlongErrorMessageToFailedResult(): void
    {
        $result = StepResult::failed('::message::');

        self::assertEquals('::message::', $result->error());
    }
}
