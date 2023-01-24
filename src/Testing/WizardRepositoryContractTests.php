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

namespace Arcanist\Testing;

use Arcanist\AbstractWizard;
use Arcanist\Contracts\ResponseRenderer;
use Arcanist\Contracts\WizardActionResolver;
use Arcanist\Contracts\WizardRepository;
use Arcanist\Exception\WizardNotFoundException;
use Arcanist\Tests\Fixtures\WizardA;
use Arcanist\Tests\Fixtures\WizardB;
use Mockery as m;

trait WizardRepositoryContractTests
{
    /**
     * @test
     *
     * @group WizardRepository
     */
    public function it_can_save_and_retrieve_wizard_data(): void
    {
        /** @var AbstractWizard $wizard */
        $wizard = m::mock(AbstractWizard::class)->makePartial();
        $repository = $this->createRepository();

        $repository->saveData($wizard, [
            'foo' => 'bar',
        ]);

        self::assertEquals(
            ['foo' => 'bar'],
            $repository->loadData($wizard),
        );
    }

    /**
     * @test
     *
     * @group WizardRepository
     */
    public function it_updates_the_wizards_data_after_saving(): void
    {
        /** @var AbstractWizard $wizard */
        $wizard = m::mock(AbstractWizard::class)->makePartial();
        $repository = $this->createRepository();

        $repository->saveData($wizard, ['foo' => 'bar']);
        self::assertEquals('bar', $wizard->data('foo'));

        $repository->saveData($wizard, ['foo' => 'baz']);
        self::assertEquals('baz', $wizard->data('foo'));
    }

    /**
     * @test
     *
     * @group WizardRepository
     */
    public function it_throws_an_exception_when_trying_to_load_a_wizard_that_doesnt_exist(): void
    {
        $repository = $this->createRepository();

        /** @var AbstractWizard $wizard */
        $wizard = m::mock(AbstractWizard::class)->makePartial();
        $wizard->setId(1);

        $this->expectException(WizardNotFoundException::class);

        $repository->loadData($wizard);
    }

    /**
     * @test
     *
     * @group WizardRepository
     */
    public function it_creates_a_new_wizard_if_saving_for_the_first_time(): void
    {
        /** @var AbstractWizard $wizard */
        $wizard = m::mock(AbstractWizard::class)->makePartial();
        $repository = $this->createRepository();

        $repository->saveData($wizard, []);

        self::assertNotNull($wizard->getId());
    }

    /**
     * @test
     *
     * @group WizardRepository
     */
    public function it_updates_an_existing_wizard(): void
    {
        $repository = $this->createRepository();

        /** @var AbstractWizard $wizard */
        $wizard = m::mock(AbstractWizard::class)->makePartial();

        // First, create the wizard we want to update
        $repository->saveData($wizard, ['foo' => 'bar']);
        $wizardId = $wizard->getId();

        // Then, update it immediately afterwards
        $repository->saveData($wizard, ['foo' => 'baz']);

        self::assertEquals(
            $wizardId,
            $wizard->getId(),
            "Expected wizard id to stay the same but it didn't. `saveData` should not create a new record if the wizard already has an id.",
        );
        self::assertEquals(
            ['foo' => 'baz'],
            $repository->loadData($wizard),
        );
    }

    /**
     * @test
     *
     * @group WizardRepository
     */
    public function it_keeps_track_of_each_wizard_data_separately(): void
    {
        $repository = $this->createRepository();

        /** @var AbstractWizard $wizard1 */
        $wizard1 = m::mock(AbstractWizard::class)->makePartial();

        /** @var AbstractWizard $wizard2 */
        $wizard2 = m::mock(AbstractWizard::class)->makePartial();

        $repository->saveData($wizard1, ['foo' => 'bar']);
        $repository->saveData($wizard2, ['foo' => 'baz']);

        self::assertEquals(['foo' => 'bar'], $repository->loadData($wizard1));
        self::assertEquals(['foo' => 'baz'], $repository->loadData($wizard2));
    }

    /**
     * @test
     *
     * @group WizardRepository
     */
    public function it_merges_new_data_with_the_existing_data(): void
    {
        $repository = $this->createRepository();

        /** @var AbstractWizard $wizard */
        $wizard = m::mock(AbstractWizard::class)->makePartial();

        $repository->saveData($wizard, [
            '::key-1::' => '::old-value-1::',
            '::key-2::' => '::old-value-2::',
        ]);

        $repository->saveData($wizard, [
            '::key-2::' => '::new-value-2::',
            '::key-3::' => '::value-3::',
        ]);

        $this->assertEquals([
            '::key-1::' => '::old-value-1::',
            '::key-2::' => '::new-value-2::',
            '::key-3::' => '::value-3::',
        ], $repository->loadData($wizard));
    }

    /**
     * @test
     *
     * @group WizardRepository
     */
    public function it_deletes_a_wizard(): void
    {
        $repository = $this->createRepository();

        /** @var AbstractWizard $wizard */
        $wizard = m::mock(AbstractWizard::class)->makePartial();

        // Assuming there is an existing wizard
        $repository->saveData($wizard, []);

        // After we deleted it...
        $repository->deleteWizard($wizard);

        // Trying to load it again should throw an exception
        $this->expectException(WizardNotFoundException::class);
        $repository->loadData($wizard);
    }

    /**
     * @test
     *
     * @group WizardRepository
     */
    public function it_unsets_the_wizard_id_after_deleting_it(): void
    {
        $repository = $this->createRepository();

        /** @var AbstractWizard $wizard */
        $wizard = m::mock(AbstractWizard::class)->makePartial();

        // Assuming there is an existing wizard
        $repository->saveData($wizard, []);

        // After we deleted it...
        $repository->deleteWizard($wizard);

        // It's id should be `null`
        $this->assertNull($wizard->getId());
    }

    /**
     * @test
     *
     * @group WizardRepository
     */
    public function it_does_not_unset_the_wizards_it_(): void
    {
        $repository = $this->createRepository();

        // Assuming we have previously saved an wizard.
        $wizardA = $this->makeWizard(WizardA::class, $repository);
        $repository->saveData($wizardA, []);

        // Attempting to load a different type of wizard with the same id
        // should result in an exception.
        $wizardB = $this->makeWizard(WizardB::class, $repository, $wizardA->getId());

        $this->expectException(WizardNotFoundException::class);

        $repository->loadData($wizardB);
    }

    /**
     * @test
     *
     * @group WizardRepository
     */
    public function it_throws_an_exception_when_trying_to_save_a_wizard_but_a_wizard_with_the_same_id_but_different_class_already_exists(): void
    {
        $repository = $this->createRepository();

        // Assuming we have previously saved an wizard.
        $wizardA = $this->makeWizard(WizardA::class, $repository);
        $repository->saveData($wizardA, []);

        // Attempting to save a different type of wizard with the same id
        // should result in an exception.
        $wizardB = $this->makeWizard(WizardB::class, $repository, $wizardA->getId());

        $this->expectException(WizardNotFoundException::class);

        $repository->saveData($wizardB, []);
    }

    /**
     * @test
     *
     * @group WizardRepository
     */
    public function it_does_not_delete_the_wizards_class_and_id_dont_match(): void
    {
        $repository = $this->createRepository();

        // Assuming we have previously saved an wizard.
        $wizardA = $this->makeWizard(WizardA::class, $repository);
        $repository->saveData($wizardA, []);
        $expectdId = $wizardA->getId();

        // Attempting to delete a different type of wizard with the same id
        // should not unset $wizardA id.
        $wizardB = $this->makeWizard(WizardB::class, $repository, $wizardA->getId());

        $repository->deleteWizard($wizardB);

        // $wizardB should not have been deleted
        $this->assertNotNull($wizardB->getId());
        // $wizardA should not have been deleted
        $this->assertEquals($expectdId, $wizardA->getId());
    }

    abstract protected function createRepository(): WizardRepository;

    /**
     * @param class-string<AbstractWizard> $class
     */
    private function makeWizard(string $class, WizardRepository $repository, mixed $id = null): AbstractWizard
    {
        $wizard = new $class(
            $repository,
            m::mock(ResponseRenderer::class),
            m::mock(WizardActionResolver::class),
        );

        if (null !== $id) {
            $wizard->setId($id);
        }

        return $wizard;
    }
}
