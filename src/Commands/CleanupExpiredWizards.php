<?php declare(strict_types=1);

namespace Sassnowski\Arcanist\Commands;

use Sassnowski\Arcanist\TTL;
use Illuminate\Console\Command;
use Sassnowski\Arcanist\Repository\Wizard;

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
           ->get()
           ->each(function (Wizard $wizard) {
               $wizard->class::onExpire($wizard->data);

               $wizard->delete();
           });
    }
}
