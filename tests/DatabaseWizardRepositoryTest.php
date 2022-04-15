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
use Arcanist\Repository\DatabaseWizardRepository;
use Arcanist\Testing\WizardRepositoryContractTests;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DatabaseWizardRepositoryTest extends TestCase
{
    use RefreshDatabase;
    use WizardRepositoryContractTests;

    protected function createRepository(): WizardRepository
    {
        return new DatabaseWizardRepository();
    }
}
