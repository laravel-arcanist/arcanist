<?php declare(strict_types=1);

namespace Arcanist\Repository;

use Arcanist\TTL;
use Illuminate\Support\Str;
use Arcanist\AbstractWizard;
use Illuminate\Support\Facades\Cache;
use Arcanist\Contracts\WizardRepository;
use Arcanist\Exception\WizardNotFoundException;

class CacheWizardRepository implements WizardRepository
{
    private string $keyPrefix = 'arcanist.';

    public function __construct(private TTL $ttl)
    {
    }

    public function saveData(AbstractWizard $wizard, array $data): void
    {
        if (!$wizard->exists()) {
            $wizard->setId(Str::orderedUuid());

            $this->store($wizard, $data);

            return;
        }

        $cacheKey = $this->buildCacheKey($wizard);

        if (!Cache::has($cacheKey)) {
            throw new WizardNotFoundException();
        }

        $this->store($wizard, array_merge(Cache::get($cacheKey, []), $data));
    }

    public function deleteWizard(AbstractWizard $wizard): void
    {
        $cacheKey = $this->buildCacheKey($wizard);

        if (!Cache::has($cacheKey)) {
            return;
        }

        Cache::delete($cacheKey);
        $wizard->setId(null);
    }

    public function loadData(AbstractWizard $wizard): array
    {
        return $this->loadWizard($wizard);
    }

    private function loadWizard(AbstractWizard $wizard): array
    {
        $key = $this->keyPrefix . $wizard::class . '.' . $wizard->getId();

        if (!Cache::has($key)) {
            throw new WizardNotFoundException();
        }

        return Cache::get($key);
    }

    private function buildCacheKey(AbstractWizard $wizard): string
    {
        return $this->keyPrefix . $wizard::class . '.' . $wizard->getId();
    }

    private function store(AbstractWizard $wizard, array $data): void
    {
        Cache::put(
            $this->buildCacheKey($wizard),
            $data,
            $this->ttl->toSeconds()
        );
    }
}
