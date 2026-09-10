<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Tasks;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Misaf\VendraTenant\Contracts\TenantContract;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Tasks\SwitchTenantTask;

final class SwitchMailTask implements SwitchTenantTask
{
    private ?string $currentTenantMailer = null;

    /**
     * @var array<mixed>
     */
    private readonly array $originalMailers;

    private readonly string $originalDefaultDriver;

    private readonly string $originalFromAddress;

    private readonly string $originalFromName;

    public function __construct()
    {
        $this->originalMailers = Config::array('mail.mailers');
        $this->originalDefaultDriver = Config::string('mail.default');
        $this->originalFromAddress = Config::string('mail.from.address');
        $this->originalFromName = Config::string('mail.from.name');
    }

    public function forgetCurrent(): void
    {
        if ($this->currentTenantMailer !== null) {
            Mail::purge($this->currentTenantMailer);

            $this->currentTenantMailer = null;
        }

        Config::set('mail.mailers', $this->originalMailers);

        Mail::setDefaultDriver($this->originalDefaultDriver);
        Mail::alwaysFrom($this->originalFromAddress, $this->originalFromName);
    }

    public function makeCurrent(IsTenant $tenant): void
    {
        if (! $tenant instanceof TenantContract) {
            return;
        }

        $this->currentTenantMailer = $tenant->getTenantSlug();

        Config::set("mail.mailers.{$this->currentTenantMailer}", Config::array('mail.mailers.smtp'));

        Mail::setDefaultDriver($this->currentTenantMailer);
        Mail::alwaysFrom('support@example.com', "{$tenant->getTenantName()} [Support]");
    }
}
