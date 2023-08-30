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

namespace Arcanist\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

class WizardMakeCommand extends GeneratorCommand
{
    protected $name = 'make:wizard';
    protected $type = 'Wizard';
    protected $description = 'Create a new Arcanist wizard';

    public function handle(): ?bool
    {
        parent::handle();

        foreach ($this->getSteps() as $step) {
            $this->call('make:wizard-step', [
                'name' => $step,
                'wizard' => $this->getNameInput(),
            ]);
        }

        return null;
    }

    protected function getStub(): string
    {
        return __DIR__ . '/stubs/wizard.stub';
    }

    protected function buildClass($name): string
    {
        $stub = $this->files->get($this->getStub());

        return $this->replaceNamespace($stub, $name)
            ->replaceWizardTitle($stub)
            ->replaceWizardSlug($stub)
            ->replaceSteps($stub)
            ->replaceClass($stub, $name);
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace . '\Wizards\\' . $this->getNameInput();
    }

    /**
     * @return array<int, array<int, null|int|string>>
     */
    protected function getOptions(): array
    {
        return [
            ['steps', null, InputOption::VALUE_OPTIONAL, 'bla'],
        ];
    }

    private function replaceWizardTitle(string &$stub): self
    {
        $stub = \str_replace('{{ title }}', $this->getNameInput(), $stub);

        return $this;
    }

    private function replaceWizardSlug(string &$stub): self
    {
        $stub = \str_replace('{{ slug }}', Str::kebab($this->getNameInput()), $stub);

        return $this;
    }

    private function replaceSteps(string &$stub): self
    {
        $steps = $this->getSteps();

        if (empty($steps)) {
            $steps = 'protected array $steps = [];';
        } else {
            $steps = [
                'protected array $steps = [',
                ...\array_map(
                    fn (string $step) => '        \App\Wizards\\' . $this->getNameInput() . '\Steps\\' . $step . '::class,',
                    $steps,
                ),
                '    ];',
            ];

            $steps = \implode("\n", $steps);
        }

        $stub = \str_replace('{{ steps }}', $steps, $stub);

        return $this;
    }

    /**
     * @return array<int, string>
     */
    private function getSteps(): array
    {
        /** @var string $steps */
        $steps = $this->option('steps');

        if (empty($steps)) {
            return [];
        }

        return \explode(',', $steps);
    }
}
