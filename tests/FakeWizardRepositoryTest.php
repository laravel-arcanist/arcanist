<?php declare(strict_types=1);

namespace Arcanist\Tests;

use Arcanist\Contracts\WizardRepository;
use Arcanist\Repository\FakeWizardRepository;
use Arcanist\Testing\WizardRepositoryContractTests;

class FakeWizardRepositoryTest extends TestCase
{
    use WizardRepositoryContractTests;

    protected function createRepository(): WizardRepository
    {
        return new FakeWizardRepository();
    }
}
