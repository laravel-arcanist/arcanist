<?php declare(strict_types=1);

namespace Arcanist\Repository;

use Arcanist\TTL;
use Illuminate\Support\Str;
use Arcanist\AbstractWizard;
use Illuminate\Support\Facades\Session;
use Arcanist\Contracts\WizardRepository;
use Arcanist\Exception\WizardNotFoundException;

class SessionWizardRepository implements WizardRepository
{
    private string $keyPrefix = 'arcanist.';

    public function __construct(private TTL $ttl)
    {
    }

    public function saveData(AbstractWizard $wizard, array $data): void
    {
        if (!$wizard->exists()) {

            $sessionLen = (is_countable(Session::get($this->getSessionPath($wizard)))) ? count(Session::get($this->getSessionPath($wizard))) : 0;
            $wizard->setId($sessionLen + 1);

            $this->store($wizard, $data);

            return;
        }

        $sessionKey = $this->buildSessionKey($wizard);

        if (!Session::has($sessionKey)) {
            throw new WizardNotFoundException();
        }

        $this->store($wizard, array_merge(Session::get($sessionKey, []), $data));
    }

    public function deleteWizard(AbstractWizard $wizard): void
    {
        $sessionKey = $this->buildSessionKey($wizard);

        if (!Session::has($sessionKey)) {
            return;
        }

        Session::forget($sessionKey);
        $wizard->setId(null);
    }

    public function loadData(AbstractWizard $wizard): array
    {
        return $this->loadWizard($wizard);
    }

    private function loadWizard(AbstractWizard $wizard): array
    {
        $sessionKey = $this->buildSessionKey($wizard);

        if (!Session::has($sessionKey)) {
            throw new WizardNotFoundException();
        }

        return Session::get($sessionKey);
    }

    private function getSessionPath(AbstractWizard $wizard): string
    {
        return $this->keyPrefix . $wizard::class;
    }

    private function buildSessionKey(AbstractWizard $wizard): string
    {
        return $this->getSessionPath($wizard) . '.' . $wizard->getId();
    }

    private function store(AbstractWizard $wizard, array $data): void
    {
        Session::put(
            $this->buildSessionKey($wizard),
            $data
        );
    }
}
