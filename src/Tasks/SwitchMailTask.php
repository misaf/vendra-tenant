<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Tasks;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Misaf\VendraTenant\Models\Tenant;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Tasks\SwitchTenantTask;

final class SwitchMailTask implements SwitchTenantTask
{
    private ?string $currentTenantMailer = null;

    /**
     * @var array<mixed>
     */
    private array $originalMailers;

    private string $originalDefaultDriver;

    private string $originalFromAddress;

    private string $originalFromName;

    public function __construct()
    {
        $this->originalMailers = Config::array('mail.mailers');
        $this->originalDefaultDriver = Config::string('mail.default');
        $this->originalFromAddress = Config::string('mail.from.address');
        $this->originalFromName = Config::string('mail.from.name');
    }

    public function forgetCurrent(): void
    {
        if (null !== $this->currentTenantMailer) {
            Mail::purge($this->currentTenantMailer);

            $this->currentTenantMailer = null;
        }

        Config::set('mail.mailers', $this->originalMailers);

        Mail::setDefaultDriver($this->originalDefaultDriver);
        Mail::alwaysFrom($this->originalFromAddress, $this->originalFromName);
    }

    /**
     * @param  Tenant  $tenant
     */
    public function makeCurrent(IsTenant $tenant): void
    {
        $this->currentTenantMailer = $tenant->slug;

        Config::set("mail.mailers.{$this->currentTenantMailer}", Config::array('mail.mailers.smtp'));

        Mail::setDefaultDriver($this->currentTenantMailer);
        Mail::alwaysFrom('support@example.com', "{$tenant->name} [Support]");
    }
}
