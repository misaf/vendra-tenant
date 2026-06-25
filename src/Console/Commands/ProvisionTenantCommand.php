<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Misaf\VendraTenant\Actions\ProvisionTenantAction;

final class ProvisionTenantCommand extends Command implements PromptsForMissingInput
{
    protected $signature = 'vendra-tenant:provision
        {name : Tenant name}
        {domain : Tenant domain}
        {username : Username for the tenant owner}
        {email : Email address for the tenant owner}
        {--seed : Run default tenant seeders after provisioning}';

    protected $description = 'Provision a tenant with a domain, owner user, and role assignment';

    public function __construct(private readonly ProvisionTenantAction $provisionTenantAction)
    {
        parent::__construct();
    }

    /**
     * @return array<string, string|array<int, string>>
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'name'     => ['Tenant name', 'Acme'],
            'domain'   => ['Tenant domain', 'acme.test'],
            'username' => ['Username for the tenant owner', 'admin_acme'],
            'email'    => ['Email address for the tenant owner', 'admin@acme.test'],
        ];
    }

    public function handle(): int
    {
        $data = $this->validatedInput();

        if (null === $data) {
            return self::FAILURE;
        }

        $shouldSeed = $this->shouldSeedTenant();

        $result = $this->provisionTenantAction->execute($data, $shouldSeed);

        $this->info('Tenant provisioned.');
        $this->table(['Field', 'Value'], [
            ['Domain', $data['domain']],
            ['Username', $result['user']->username],
            ['Email', $result['user']->email],
            ['Password', $result['password']],
            ['Seeders', $shouldSeed ? 'Run' : 'Skipped'],
        ]);

        return self::SUCCESS;
    }

    /**
     * @return array{
     *     name: string,
     *     domain: string,
     *     username: string,
     *     email: string
     * }|null
     */
    private function validatedInput(): ?array
    {
        $input = [
            'name'     => $this->argument('name'),
            'domain'   => $this->argument('domain'),
            'username' => $this->argument('username'),
            'email'    => $this->argument('email'),
        ];

        $validator = Validator::make($input, [
            'name'   => ['required', 'string', 'max:255'],
            'domain' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tenant_domains', 'name')->withoutTrashed(),
            ],
            'username' => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->withoutTrashed()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return null;
        }

        /** @var array{name: string, domain: string, username: string, email: string} $data */
        $data = $validator->validated();

        return $data;
    }

    private function shouldSeedTenant(): bool
    {
        if ((bool) $this->option('seed')) {
            return true;
        }

        if ( ! $this->input->isInteractive()) {
            return false;
        }

        return $this->confirm('Run default tenant seeders?', true);
    }
}
