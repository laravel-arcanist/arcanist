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

namespace Arcanist\Testing;

use Arcanist\AbstractWizard;
use Arcanist\Arcanist;
use Arcanist\Contracts\ResponseRenderer;
use Arcanist\Exception\StepTemplateNotFoundException;
use Arcanist\WizardStep;
use Illuminate\Testing\TestResponse;
use Mockery as m;

/**
 * @mixin TestCase
 */
trait ResponseRendererContractTests
{
    /**
     * @test
     */
    public function throws_an_exception_if_the_template_does_not_exist(): void
    {
        $wizard = m::mock(AbstractWizard::class)->makePartial();
        $wizard->allows('summary')->andReturns(['::summary::']);
        $wizard::$slug = 'wizard-slug';
        $step = m::mock(WizardStep::class)->makePartial();
        $step->slug = 'step-with-non-existent-template';

        $this->expectException(StepTemplateNotFoundException::class);
        $this->expectErrorMessage('No template found for step [step-with-non-existent-template].');

        $this->makeRenderer()
            ->renderStep($step, $wizard, []);
    }

    /**
     * @test
     */
    public function it_redirects_to_the_first_step_if_the_wizard_does_not_exist_yet(): void
    {
        $wizard = m::mock(AbstractWizard::class)->makePartial();
        $wizard::$slug = '::wizard::';
        $wizard->allows('exists')->andReturnFalse();
        $step = m::mock(WizardStep::class);

        Arcanist::boot([$wizard::class]);

        $response = new TestResponse($this->makeRenderer()->redirect($step, $wizard));

        $response->assertRedirect(route('wizard.::wizard::.create'));
    }

    /**
     * @test
     */
    public function it_redirects_with_an_error(): void
    {
        $wizard = m::mock(AbstractWizard::class)->makePartial();
        $wizard::$slug = '::wizard::';
        $wizard->setId(1);
        $wizard->allows('exists')->andReturnFalse();
        $step = m::mock(WizardStep::class);

        Arcanist::boot([$wizard::class]);

        $response = new TestResponse(
            $this->makeRenderer()->redirectWithError($step, $wizard, '::message::'),
        );

        $response->assertSessionHasErrors('wizard');
    }

    abstract protected function makeRenderer(): ResponseRenderer;
}
