<?php declare(strict_types=1);

namespace Tests;

use Sassnowski\Arcanist\Contracts\WizardRepository;
use Sassnowski\Arcanist\Repository\FakeWizardRepository;

class FakeWizardRepositoryTest extends TestCase
{
    use WizardRepositoryContractTests;

    protected function createRepository(): WizardRepository
    {
        return new FakeWizardRepository();
    }
}
