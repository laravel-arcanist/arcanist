<?php declare(strict_types=1);

namespace Sassnowski\Arcanist\Event;

use Sassnowski\Arcanist\AbstractAssistant;

final class AssistantLoaded
{
    public function __construct(public AbstractAssistant $assistant)
    {
    }
}
