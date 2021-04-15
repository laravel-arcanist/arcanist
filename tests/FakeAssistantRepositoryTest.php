<?php declare(strict_types=1);

namespace Tests;

use Mockery as m;
use Illuminate\Http\RedirectResponse;
use Sassnowski\Arcanist\AbstractAssistant;
use Sassnowski\Arcanist\Contracts\ResponseRenderer;
use Sassnowski\Arcanist\Contracts\AssistantRepository;
use Sassnowski\Arcanist\Repository\FakeAssistantRepository;
use Sassnowski\Arcanist\Exception\AssistantNotFoundException;

class FakeAssistantRepositoryTest extends TestCase
{
    /** @test */
    public function it_sets_the_assistants_id_when_storing_it_for_the_first_time(): void
    {
        $repo = new FakeAssistantRepository();
        $assistant1 = $this->createAssistant(AssistantA::class);
        $assistant2 = $this->createAssistant(AssistantA::class);

        $repo->saveData($assistant1, ['foo' => 'bar']);
        self::assertEquals(1, $assistant1->getId());

        $repo->saveData($assistant2, ['foo' => 'bar']);
        self::assertEquals(2, $assistant2->getId());
    }

    /** @test */
    public function it_keeps_track_of_each_assistants_data_separately(): void
    {
        $repo = new FakeAssistantRepository();
        $assistant1 = $this->createAssistant(AssistantA::class, 1);
        $assistant2 = $this->createAssistant(AssistantA::class, 2);

        $repo->saveData($assistant1, ['foo' => 'bar']);
        $repo->saveData($assistant2, ['foo' => 'baz']);

        self::assertEquals(['foo' => 'bar'], $repo->loadData($assistant1));
        self::assertEquals(['foo' => 'baz'], $repo->loadData($assistant2));
    }

    /** @test */
    public function it_appends_new_keys_to_the_existing_data(): void
    {
        $repo = new FakeAssistantRepository([
            AssistantA::class => [
                1 => [
                    'foo' => 'bar',
                ],
            ],
        ]);
        $assistant = $this->createAssistant(AssistantA::class, 1);

        $repo->saveData($assistant, ['baz' => 'qux']);

        self::assertEquals(
            ['foo' => 'bar', 'baz' => 'qux'],
            $repo->loadData($assistant)
        );
    }

    /** @test */
    public function it_does_not_create_a_new_record_if_the_assistant_already_has_an_id(): void
    {
        $repo = new FakeAssistantRepository();
        $assistant = $this->createAssistant(AssistantA::class, 5);

        $repo->saveData($assistant, ['foo' => 'bar']);
        self::assertEquals(5, $assistant->getId());
    }

    /** @test */
    public function it_returns_all_data_if_no_key_is_provided(): void
    {
        $data = [
            'foo' => 'bar',
            'baz' => 'qux',
        ];
        $repo = new FakeAssistantRepository([
            AssistantA::class => [
                1 => $data
            ]
        ]);
        $assistant = $this->createAssistant(AssistantA::class, 1);

        self::assertEquals($data, $repo->loadData($assistant));
    }

    /** @test */
    public function it_cannot_load_data_from_a_different_type_of_assistant(): void
    {
        $this->expectException(AssistantNotFoundException::class);

        $repo = new FakeAssistantRepository();
        $assistantA = $this->createAssistant(AssistantA::class);

        $repo->saveData($assistantA, ['foo' => 'bar']);

        $assistantB = $this->createAssistant(AssistantB::class);
        $assistantB->setId($assistantA->getId());

        $repo->loadData($assistantB);
    }

    /** @test */
    public function it_deletes_data_about_an_assistant(): void
    {
        $this->expectException(AssistantNotFoundException::class);

        $repo = new FakeAssistantRepository([
            AssistantA::class => [
                1 => ['foo' => 'bar']
            ]
        ]);
        $assistantA = $this->createAssistant(AssistantA::class, 1);

        $repo->deleteAssistant($assistantA);

        $repo->loadData($assistantA);
    }

    /** @test */
    public function it_sets_the_assistants_id_to_null_after_deleting_it(): void
    {
        $repo = new FakeAssistantRepository([
            AssistantA::class => [
                1 => ['foo' => 'bar']
            ]
        ]);
        $assistantA = $this->createAssistant(AssistantA::class, 1);

        $repo->deleteAssistant($assistantA);

        self::assertNull($assistantA->getId());
    }

    private function createAssistant(string $class, ?int $id = null): AbstractAssistant
    {
        $assistant = new $class(m::mock(AssistantRepository::class), m::mock(ResponseRenderer::class));

        if ($id !== null) {
            $assistant->setId($id);
        }

        return $assistant;
    }
}

class AssistantA extends AbstractAssistant
{
    protected function onAfterComplete(): RedirectResponse
    {
        return redirect();
    }
}

class AssistantB extends AbstractAssistant
{
    protected function onAfterComplete(): RedirectResponse
    {
        return redirect();
    }
}
