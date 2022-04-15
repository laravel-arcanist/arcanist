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

use Arcanist\Contracts\WizardRepository;
use Arcanist\Repository\FakeWizardRepository;
use Arcanist\Testing\WizardRepositoryContractTests;

class FakeWizardRepositoryTest extends TestCase
{
    use WizardRepositoryContractTests;

    protected function createRepository(): WizardRepository
    {
        return new FakeWizardRepository();
    }
}
