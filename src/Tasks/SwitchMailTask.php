<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Tasks;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Tasks\SwitchTenantTask;

/**
 * Task to switch mail configuration based on the current tenant.
 *
 * This task handles switching mail settings including SMTP configuration
 * and from address/name based on the tenant slug.
 */
final class SwitchMailTask implements SwitchTenantTask
{
    private string $originalFromAddress;

    private string $originalFromName;

    public function __construct()
    {
        $this->originalFromAddress = Config::string('mail.from.address');
        $this->originalFromName = Config::string('mail.from.name');
    }

    public function forgetCurrent(): void
    {
        Mail::alwaysFrom($this->originalFromAddress, $this->originalFromName);
    }

    public function makeCurrent(IsTenant $tenant): void
    {
        $tenantSlug = $tenant->slug;

        // Configure SMTP settings for the tenant using shared configuration
        $this->configureSmtpForTenant($tenantSlug);

        // Set the default mail driver
        Mail::setDefaultDriver($tenantSlug);

        // Set from address and name for the tenant
        $mailSettings = $this->getMailSettingsForTenant($tenantSlug);
        if ($mailSettings) {
            Mail::alwaysFrom($mailSettings['address'], $mailSettings['name']);
        }
    }

    private function configureSmtpForTenant(string $tenantSlug): void
    {
        // Use the default SMTP configuration for all tenants
        $smtpConfig = Config::array('mail.mailers.smtp');

        Config::set("mail.mailers.{$tenantSlug}", $smtpConfig);
    }

    /**
     * @return array<string, string>|null
     */
    private function getMailSettingsForTenant(string $slug): ?array
    {
        return [
            'address' => 'support@example.com',
            'name'    => 'Example [Support]',
        ];
    }
}
