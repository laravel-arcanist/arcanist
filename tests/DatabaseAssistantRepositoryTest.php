<?php declare(strict_types=1);

namespace Tests;

use function tap;
use Mockery as m;
use function get_class;
use Illuminate\Support\Arr;
use Illuminate\Http\RedirectResponse;
use Sassnowski\Arcanist\AbstractAssistant;
use Sassnowski\Arcanist\Repository\Assistant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Sassnowski\Arcanist\Exception\AssistantNotFoundException;
use Sassnowski\Arcanist\Repository\DatabaseAssistantRepository;

class DatabaseAssistantRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private DatabaseAssistantRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new DatabaseAssistantRepository();
    }

    /** @test */
    public function it_can_save_data_for_an_assistant(): void
    {
        $assistant = m::mock(AbstractAssistant::class)->makePartial();

        $this->repository->saveData($assistant, [
            'foo' => 'bar',
        ]);

        $this->assertAssistantExistsInDatabase([
            'class' => get_class($assistant),
            'data' => ['foo' => 'bar'],
        ]);
    }

    /** @test */
    public function it_sets_the_assistants_id_when_it_gets_created(): void
    {
        $assistant = m::mock(AbstractAssistant::class)->makePartial();

        $this->repository->saveData($assistant, []);

        self::assertNotNull($assistant->getId());
    }

    /** @test */
    public function it_updates_an_existing_assistant(): void
    {
        $assistant = $this->createAssistantWithData(['foo' => 'bar']);
        $oldId = $assistant->getId();

        $this->repository->saveData($assistant, ['foo' => 'baz']);

        self::assertDatabaseCount('assistants', 1);
        self::assertAssistantExistsInDatabase([
            'id' => $assistant->getId(),
            'data' => ['foo' => 'baz'],
        ]);
        self::assertEquals($oldId, $assistant->getId());
    }

    /** @test */
    public function it_merges_the_new_data_with_the_existing_data(): void
    {
        $assistant = $this->createAssistantWithData([
            '::key-1::' => '::old-value-1::',
            '::key-2::' => '::old-value-2::',
        ]);

        $this->repository->saveData($assistant, [
            '::key-2::' => '::new-value-2::',
            '::key-3::' => '::value-3::'
        ]);

        $this->assertAssistantExistsInDatabase([
            'id' => $assistant->getId(),
            'data' => [
                '::key-1::' => '::old-value-1::',
                '::key-2::' => '::new-value-2::',
                '::key-3::' => '::value-3::'
            ],
        ]);
    }

    /** @test */
    public function it_throws_an_exception_if_the_requested_id_and_tenant_class_dont_match(): void
    {
        $this->expectException(AssistantNotFoundException::class);

        $existingAssistant = $this->createAssistantWithData([]);
        $otherAssistant = m::mock(OtherAssistant::class)->makePartial();
        $otherAssistant->setId($existingAssistant->getId());

        $this->repository->saveData($otherAssistant, []);
    }

    /** @test */
    public function it_fetches_the_data_for_a_given_assistant(): void
    {
        $assistant = $this->createAssistantWithData(['::key::' => '::value::']);

        self::assertEquals(
            ['::key::' => '::value::'],
            $this->repository->loadData($assistant)
        );
    }

    /** @test */
    public function it_deletes_an_assistant(): void
    {
        $assistant = $this->createAssistantWithData([]);

        $this->repository->deleteAssistant($assistant);

        self::assertDatabaseMissing('assistants', ['id' => $assistant->getId()]);
    }

    /** @test */
    public function it_unsets_the_assistants_id_after_deleting_it(): void
    {
        $assistant = $this->createAssistantWithData([]);

        $this->repository->deleteAssistant($assistant);

        self::assertNull($assistant->getId());
    }

    /** @test */
    public function it_returns_all_registered_assistants(): void
    {
        $repository = new DatabaseAssistantRepository([OtherAssistant::class]);

        self::assertEquals([OtherAssistant::class], $repository->registeredAssistants());
    }

    /** @test */
    public function it_throws_an_exception_when_deleting_an_assistant_but_the_id_and_class_dont_match(): void
    {
        $this->expectException(AssistantNotFoundException::class);

        $existingAssistant = $this->createAssistantWithData([]);
        $otherAssistant = m::mock(OtherAssistant::class)->makePartial();
        $otherAssistant->setId($existingAssistant->getId());

        $this->repository->deleteAssistant($otherAssistant);
    }

    private function createAssistantWithData(array $data)
    {
        return tap(m::mock(AbstractAssistant::class)->makePartial(), function ($assistant) use ($data) {
            $model = Assistant::create([
                'class' => get_class($assistant),
                'data' => $data,
            ]);

            $assistant->setId($model->id);

            return $assistant;
        });
    }

    private function assertAssistantExistsInDatabase(array $attributes)
    {
        if (!isset($attributes['data'])) {
            self::assertDatabaseHas('assistants', $attributes);
            return;
        }

        $assistant = Assistant::where(Arr::except($attributes, 'data'))->first();
        self::assertNotNull($assistant, 'No assistant found with the provided attributes.');
        self::assertEquals($attributes['data'], $assistant->data);
    }
}

class OtherAssistant extends AbstractAssistant
{
    protected function onAfterComplete(): RedirectResponse
    {
        return redirect()->back();
    }
}
