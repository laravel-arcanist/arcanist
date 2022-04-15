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

use Arcanist\AbstractWizard;
use Arcanist\Contracts\WizardRepository;
use Arcanist\Exception\WizardNotFoundException;
use Arcanist\Repository\CacheWizardRepository;
use Arcanist\Testing\WizardRepositoryContractTests;
use Arcanist\TTL;
use Mockery as m;

class CacheWizardRepositoryTest extends TestCase
{
    use WizardRepositoryContractTests;

    public function testItSavesWizardDataWithTheCorrectTtl(): void
    {
        /** @var AbstractWizard $wizard */
        $wizard = m::mock(AbstractWizard::class)->makePartial();
        $repository = new CacheWizardRepository(TTL::fromSeconds(60));

        $this->travelTo(now());
        $repository->saveData($wizard, ['::data::']);

        $this->travel(61)->seconds();
        $this->expectException(WizardNotFoundException::class);
        $repository->loadData($wizard);
    }

    public function testItRefreshesTheTtlWhenTheWizardGetsUpdated(): void
    {
        /** @var AbstractWizard $wizard */
        $wizard = m::mock(AbstractWizard::class)->makePartial();
        $repository = new CacheWizardRepository(TTL::fromSeconds(60));

        $this->travelTo(now());
        $repository->saveData($wizard, ['::key::' => '::data-1::']);

        $this->travel(59)->seconds();
        $repository->saveData($wizard, ['::key::' => '::data-2::']);

        $this->travel(59)->seconds();
        self::assertEquals(['::key::' => '::data-2::'], $repository->loadData($wizard));
    }

    protected function createRepository(): WizardRepository
    {
        return new CacheWizardRepository(TTL::fromSeconds(24 * 60 * 60));
    }
}
