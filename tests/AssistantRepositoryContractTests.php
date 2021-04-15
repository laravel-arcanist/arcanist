<?php declare(strict_types=1);

namespace Tests;

use Mockery as m;
use Tests\Fixtures\AssistantA;
use Tests\Fixtures\AssistantB;
use Sassnowski\Arcanist\AbstractAssistant;
use Sassnowski\Arcanist\Contracts\ResponseRenderer;
use Sassnowski\Arcanist\Contracts\AssistantRepository;
use Sassnowski\Arcanist\Exception\AssistantNotFoundException;

trait AssistantRepositoryContractTests
{
    /** @test */
    public function it_can_save_and_retrieve_assistant_data(): void
    {
        /** @var AbstractAssistant $assistant */
        $assistant = m::mock(AbstractAssistant::class)->makePartial();
        $repository = $this->createRepository();

        $repository->saveData($assistant, [
            'foo' => 'bar',
        ]);

        self::assertEquals(
            ['foo' => 'bar'],
            $repository->loadData($assistant)
        );
    }

    /** @test */
    public function it_throws_an_exception_when_trying_to_load_an_assistant_that_doesnt_exist(): void
    {
        $repository = $this->createRepository();
        /** @var AbstractAssistant $assistant */
        $assistant = m::mock(AbstractAssistant::class)->makePartial();
        $assistant->setId(1);

        $this->expectException(AssistantNotFoundException::class);

        $repository->loadData($assistant);
    }

    /** @test */
    public function it_creates_a_new_assistant_if_saving_for_the_first_time(): void
    {
        /** @var AbstractAssistant $assistant */
        $assistant = m::mock(AbstractAssistant::class)->makePartial();
        $repository = $this->createRepository();

        $repository->saveData($assistant, []);

        self::assertNotNull($assistant->getId());
    }

    /** @test */
    public function it_updates_an_existing_assistant(): void
    {
        $repository = $this->createRepository();
        /** @var AbstractAssistant $assistant */
        $assistant = m::mock(AbstractAssistant::class)->makePartial();

        // First, create the assistant we want to update
        $repository->saveData($assistant, ['foo' => 'bar']);
        $assistantId = $assistant->getId();

        // Then, update it immediately afterwards
        $repository->saveData($assistant, ['foo' => 'baz']);

        self::assertEquals(
            $assistantId,
            $assistant->getId(),
            "Expected assistant id to stay the same but it didn't. `saveData` should not create a new record if the assistant already has an id."
        );
        self::assertEquals(
            ['foo' => 'baz'],
            $repository->loadData($assistant)
        );
    }

    /** @test */
    public function it_keeps_track_of_each_assistants_data_separately(): void
    {
        $repository = $this->createRepository();

        /** @var AbstractAssistant $assistant1 */
        $assistant1 = m::mock(AbstractAssistant::class)->makePartial();
        /** @var AbstractAssistant $assistant2 */
        $assistant2 = m::mock(AbstractAssistant::class)->makePartial();

        $repository->saveData($assistant1, ['foo' => 'bar']);
        $repository->saveData($assistant2, ['foo' => 'baz']);

        self::assertEquals(['foo' => 'bar'], $repository->loadData($assistant1));
        self::assertEquals(['foo' => 'baz'], $repository->loadData($assistant2));
    }

    /** @test */
    public function it_merges_new_data_with_the_existing_data(): void
    {
        $repository = $this->createRepository();
        /** @var AbstractAssistant $assistant */
        $assistant = m::mock(AbstractAssistant::class)->makePartial();

        $repository->saveData($assistant, [
            '::key-1::' => '::old-value-1::',
            '::key-2::' => '::old-value-2::',
        ]);

        $repository->saveData($assistant, [
            '::key-2::' => '::new-value-2::',
            '::key-3::' => '::value-3::'
        ]);

        $this->assertEquals([
            '::key-1::' => '::old-value-1::',
            '::key-2::' => '::new-value-2::',
            '::key-3::' => '::value-3::'
        ], $repository->loadData($assistant));
    }

    /** @test */
    public function it_deletes_an_assistant(): void
    {
        $repository = $this->createRepository();
        /** @var AbstractAssistant $assistant */
        $assistant = m::mock(AbstractAssistant::class)->makePartial();

        // Assuming there is an existing assistant
        $repository->saveData($assistant, []);

        // After we deleted it...
        $repository->deleteAssistant($assistant);

        // Trying to load it again should throw an exception
        $this->expectException(AssistantNotFoundException::class);
        $repository->loadData($assistant);
    }

    /** @test */
    public function it_unsets_the_assistants_id_after_deleting_it(): void
    {
        $repository = $this->createRepository();
        /** @var AbstractAssistant $assistant */
        $assistant = m::mock(AbstractAssistant::class)->makePartial();

        // Assuming there is an existing assistant
        $repository->saveData($assistant, []);

        // After we deleted it...
        $repository->deleteAssistant($assistant);

        // It's id should be `null`
        $this->assertNull($assistant->getId());
    }

    /** @test */
    public function it_throws_an_exception_when_trying_to_load_an_assistant_but_the_id_and_class_dont_match(): void
    {
        $repository = $this->createRepository();

        // Assuming we have previously saved an assistant.
        $assistantA = $this->makeAssistant(AssistantA::class, $repository);
        $repository->saveData($assistantA, []);

        // Attempting to load a different type of assistant with the same id
        // should result in an exception.
        $assistantB = $this->makeAssistant(AssistantB::class, $repository, $assistantA->getId());

        $this->expectException(AssistantNotFoundException::class);

        $repository->loadData($assistantB);
    }

    /** @test */
    public function it_throws_an_exception_when_trying_to_save_an_assistant_but_an_assistant_with_the_same_id_but_different_class_already_exists(): void
    {
        $repository = $this->createRepository();

        // Assuming we have previously saved an assistant.
        $assistantA = $this->makeAssistant(AssistantA::class, $repository);
        $repository->saveData($assistantA, []);

        // Attempting to save a different type of assistant with the same id
        // should result in an exception.
        $assistantB = $this->makeAssistant(AssistantB::class, $repository, $assistantA->getId());

        $this->expectException(AssistantNotFoundException::class);

        $repository->saveData($assistantB, []);
    }

    /** @test */
    public function it_throws_an_exception_when_trying_to_delete_an_assistant_but_the_id_and_class_dont_match(): void
    {
        $repository = $this->createRepository();

        // Assuming we have previously saved an assistant.
        $assistantA = $this->makeAssistant(AssistantA::class, $repository);
        $repository->saveData($assistantA, []);

        // Attempting to delete a different type of assistant with the same id
        // should result in an exception.
        $assistantB = $this->makeAssistant(AssistantB::class, $repository, $assistantA->getId());

        $this->expectException(AssistantNotFoundException::class);

        $repository->deleteAssistant($assistantB);
    }

    abstract protected function createRepository(): AssistantRepository;

    private function makeAssistant(string $class, AssistantRepository $repository, ?int $id = null): AbstractAssistant
    {
        $assistant = new $class($repository, m::mock(ResponseRenderer::class));

        if ($id !== null) {
            $assistant->setId($id);
        }

        return $assistant;
    }
}
