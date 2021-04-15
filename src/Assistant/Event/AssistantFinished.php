<?php declare(strict_types=1);

namespace Sassnowski\Arcanist\Assistant\Event;

use Sassnowski\Arcanist\Assistant\AbstractAssistant;

final class AssistantFinished
{
    public function __construct(public AbstractAssistant $assistant)
    {
    }
}
