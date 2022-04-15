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

use Arcanist\Repository\Wizard;
use Arcanist\TTL;
use Illuminate\Console\Command;

class CleanupExpiredWizards extends Command
{
    protected $signature = 'arcanist:clean-expired';
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
