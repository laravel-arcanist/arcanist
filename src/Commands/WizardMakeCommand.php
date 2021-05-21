<?php declare(strict_types=1);

namespace Arcanist\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

class WizardMakeCommand extends GeneratorCommand
{
    protected $name = 'make:wizard';

    public function handle(): void
    {
        parent::handle();

        foreach ($this->getSteps() as $step) {
            $this->call('make:wizard-step', [
                'name' => $step,
                'wizard' => $this->getNameInput(),
            ]);
        }
    }

    protected function getStub()
    {
        return __DIR__ . '/stubs/wizard.stub';
    }

    protected function buildClass($name)
    {
        $stub = $this->files->get($this->getStub());

        return $this->replaceNamespace($stub, $name)
            ->replaceWizardTitle($stub)
            ->replaceWizardSlug($stub)
            ->replaceSteps($stub)
            ->replaceClass($stub, $name);
    }


    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\Wizards\\' . $this->getNameInput();
    }

    protected function getOptions()
    {
        return [
            ['steps', null, InputOption::VALUE_OPTIONAL, 'bla'],
        ];
    }

    private function replaceWizardTitle(string &$stub): self
    {
        $stub = str_replace('{{ title }}', $this->getNameInput(), $stub);

        return $this;
    }

    private function replaceWizardSlug(string &$stub): self
    {
        $stub = str_replace('{{ slug }}', Str::kebab($this->getNameInput()), $stub);

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
                ...array_map(
                    fn (string $step) => '        \App\Wizards\\' . $this->getNameInput() . '\Steps\\' . $step . '::class,',
                    $steps
                ),
                '    ];'
            ];

            $steps = implode("\n", $steps);
        }

        $stub = str_replace('{{ steps }}', $steps, $stub);

        return $this;
    }

    private function getSteps(): array
    {
        $steps = $this->option('steps');

        if (empty($steps)) {
            return [];
        }

        return explode(',', $steps);
    }
}