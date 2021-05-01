<?php declare(strict_types=1);

namespace Arcanist\Commands;

use Arcanist\TTL;
use Arcanist\Repository\Wizard;
use Illuminate\Console\Command;

class CleanupExpiredWizards extends Command
{
    /** @var string */
    protected $signature = 'arcanist:clean-expired';

    /** @var string $description */
    protected $description = 'Clean up expired wizards.';

    public function __construct(private TTL $ttl)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        Wizard::where('updated_at', '<=', $this->ttl->expiresAfter())
            ->delete();
    }
}
