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
use Symfony\Component\Console\Input\InputArgument;

class WizardStepMakeCommand extends GeneratorCommand
{
    protected $name = 'make:wizard-step';
    protected $type = 'Wizard Step';

    protected function getStub()
    {
        return __DIR__ . '/stubs/step.stub';
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        $wizard = $this->argument('wizard');

        return $rootNamespace . '\Wizards\\' . $wizard . '\Steps';
    }

    protected function buildClass($name): string
    {
        $stub = $this->files->get($this->getStub());

        return $this->replaceNamespace($stub, $name)
            ->replaceStepTitle($stub)
            ->replaceStepSlug($stub)
            ->replaceClass($stub, $name);
    }

    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the class'],
            ['wizard', InputArgument::REQUIRED, 'The name of the wizard'],
        ];
    }

    private function replaceStepTitle(string &$stub): self
    {
        $stub = \str_replace('{{ title }}', $this->getNameInput(), $stub);

        return $this;
    }

    private function replaceStepSlug(string &$stub): self
    {
        $stub = \str_replace('{{ slug }}', Str::kebab($this->getNameInput()), $stub);

        return $this;
    }
}
