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

namespace Arcanist\Contracts;

use Arcanist\AbstractWizard;
use Arcanist\Exception\WizardNotFoundException;

interface WizardRepository
{
    /**
     * @param array<string, mixed> $data
     *
     * @throws WizardNotFoundException
     */
    public function saveData(AbstractWizard $wizard, array $data): void;

    /**
     * @throws WizardNotFoundException
     *
     * @return array<string, mixed>
     */
    public function loadData(AbstractWizard $wizard): array;

    /**
     * @throws WizardNotFoundException
     */
    public function deleteWizard(AbstractWizard $wizard): void;
}
