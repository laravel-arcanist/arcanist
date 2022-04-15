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

class ActionResultTest extends \PHPUnit\Framework\TestCase
{
    public function testItCanReturnASuccessfulResult(): void
    {
        $result = ActionResult::success([]);

        self::assertInstanceOf(ActionResult::class, $result);
        self::assertTrue($result->successful());
    }

    public function testItCanReturnAFailedResult(): void
    {
        $result = ActionResult::failed();

        self::assertInstanceOf(ActionResult::class, $result);
        self::assertFalse($result->successful());
    }

    public function testItReturnsAPayloadForASuccessfulResult(): void
    {
        $result = ActionResult::success([
            '::key-1::' => '::value-1::',
            '::key-2::' => '::value-2::',
        ]);

        self::assertEquals('::value-1::', $result->get('::key-1::'));
        self::assertEquals('::value-2::', $result->get('::key-2::'));
    }

    public function testItCanPassAlongAnErrorMessageForAFailedResult(): void
    {
        $result = ActionResult::failed('::message::');

        self::assertEquals('::message::', $result->error());
    }
}
