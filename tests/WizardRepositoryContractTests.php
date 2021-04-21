<?php declare(strict_types=1);

namespace Tests;

use Mockery as m;
use Tests\Fixtures\WizardA;
use Tests\Fixtures\WizardB;
use Sassnowski\Arcanist\AbstractWizard;
use Sassnowski\Arcanist\Contracts\ResponseRenderer;
use Sassnowski\Arcanist\Contracts\WizardRepository;
use Sassnowski\Arcanist\Contracts\WizardActionResolver;
use Sassnowski\Arcanist\Exception\WizardNotFoundException;

trait WizardRepositoryContractTests
{
    /** @test */
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
            $repository->loadData($wizard)
        );
    }

    /** @test */
    public function it_throws_an_exception_when_trying_to_load_an_wizard_that_doesnt_exist(): void
    {
        $repository = $this->createRepository();
        /** @var AbstractWizard $wizard */
        $wizard = m::mock(AbstractWizard::class)->makePartial();
        $wizard->setId(1);

        $this->expectException(WizardNotFoundException::class);

        $repository->loadData($wizard);
    }

    /** @test */
    public function it_creates_a_new_wizard_if_saving_for_the_first_time(): void
    {
        /** @var AbstractWizard $wizard */
        $wizard = m::mock(AbstractWizard::class)->makePartial();
        $repository = $this->createRepository();

        $repository->saveData($wizard, []);

        self::assertNotNull($wizard->getId());
    }

    /** @test */
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
            "Expected wizard id to stay the same but it didn't. `saveData` should not create a new record if the wizard already has an id."
        );
        self::assertEquals(
            ['foo' => 'baz'],
            $repository->loadData($wizard)
        );
    }

    /** @test */
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

    /** @test */
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
            '::key-3::' => '::value-3::'
        ]);

        $this->assertEquals([
            '::key-1::' => '::old-value-1::',
            '::key-2::' => '::new-value-2::',
            '::key-3::' => '::value-3::'
        ], $repository->loadData($wizard));
    }

    /** @test */
    public function it_deletes_an_wizard(): void
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

    /** @test */
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

    /** @test */
    public function it_throws_an_exception_when_trying_to_load_an_wizard_but_the_id_and_class_dont_match(): void
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

    /** @test */
    public function it_throws_an_exception_when_trying_to_save_an_wizard_but_an_wizard_with_the_same_id_but_different_class_already_exists(): void
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

    /** @test */
    public function it_throws_an_exception_when_trying_to_delete_an_wizard_but_the_id_and_class_dont_match(): void
    {
        $repository = $this->createRepository();

        // Assuming we have previously saved an wizard.
        $wizardA = $this->makeWizard(WizardA::class, $repository);
        $repository->saveData($wizardA, []);

        // Attempting to delete a different type of wizard with the same id
        // should result in an exception.
        $wizardB = $this->makeWizard(WizardB::class, $repository, $wizardA->getId());

        $this->expectException(WizardNotFoundException::class);

        $repository->deleteWizard($wizardB);
    }

    abstract protected function createRepository(): WizardRepository;

    private function makeWizard(string $class, WizardRepository $repository, ?int $id = null): AbstractWizard
    {
        $wizard = new $class(
            $repository,
            m::mock(ResponseRenderer::class),
            m::mock(WizardActionResolver::class)
        );

        if ($id !== null) {
            $wizard->setId($id);
        }

        return $wizard;
    }
}
