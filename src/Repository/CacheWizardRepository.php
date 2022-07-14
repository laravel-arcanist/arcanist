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

namespace Arcanist\Repository;

use Arcanist\AbstractWizard;
use Arcanist\Contracts\WizardRepository;
use Arcanist\Exception\WizardNotFoundException;
use Arcanist\TTL;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CacheWizardRepository implements WizardRepository
{
    private string $keyPrefix = 'arcanist.';

    public function __construct(private TTL $ttl)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function saveData(AbstractWizard $wizard, array $data): void
    {
        if (!$wizard->exists()) {
            $wizard->setId(Str::orderedUuid());
            $wizard->setData($data);

            $this->store($wizard, $data);

            return;
        }

        $cacheKey = $this->buildCacheKey($wizard);

        if (!Cache::has($cacheKey)) {
            throw new WizardNotFoundException();
        }

        $wizard->setData($data);

        /** @var array<string, mixed> $storedData */
        $storedData = Cache::get($cacheKey, []);
        $this->store($wizard, \array_merge($storedData, $data));
    }

    public function deleteWizard(AbstractWizard $wizard): void
    {
        $cacheKey = $this->buildCacheKey($wizard);

        if (!Cache::has($cacheKey)) {
            return;
        }

        Cache::forget($cacheKey);
        $wizard->setId(null);
    }

    /**
     * @return array<string, mixed>
     */
    public function loadData(AbstractWizard $wizard): array
    {
        return $this->loadWizard($wizard);
    }

    /**
     * @return array<string, mixed>
     */
    private function loadWizard(AbstractWizard $wizard): array
    {
        $key = $this->keyPrefix . $wizard::class . '.' . $wizard->getId();

        if (!Cache::has($key)) {
            throw new WizardNotFoundException();
        }

        /** @phpstan-ignore-next-line */
        return Cache::get($key);
    }

    private function buildCacheKey(AbstractWizard $wizard): string
    {
        return $this->keyPrefix . $wizard::class . '.' . $wizard->getId();
    }

    /**
     * @param array<string, mixed> $data
     */
    private function store(AbstractWizard $wizard, array $data): void
    {
        Cache::put(
            $this->buildCacheKey($wizard),
            $data,
            $this->ttl->toSeconds(),
        );
    }
}
