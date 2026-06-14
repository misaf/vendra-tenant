<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Facades\Config;
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
        {--description= : Tenant description}
        {--domain-description= : Tenant domain description}
        {--password= : Password for the tenant owner}
        {--role= : Default role name to create and assign}
        {--role-description= : Default role description}
        {--guard=web : Guard name for the default role}
        {--verified : Mark the user email as verified}
        {--disabled : Create the tenant and domain as disabled}';

    protected $description = 'Provision a tenant with a domain, owner user, default role, and role assignment';

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

        $result = $this->provisionTenantAction->execute(
            $data,
            ! $this->option('disabled'),
            (bool) $this->option('verified'),
        );

        $this->info("Provisioned tenant {$result['tenant']->name} [{$result['tenant']->slug}] with domain [{$data['domain']}].");
        $this->info("Created user {$result['user']->username} ({$result['user']->email}) and assigned role [{$result['role']->name}].");

        return self::SUCCESS;
    }

    /**
     * @return array{
     *     name: string,
     *     domain: string,
     *     username: string,
     *     email: string,
     *     password: string,
     *     role: string,
     *     guard: string
     * }|null
     */
    private function validatedInput(): ?array
    {
        $input = [
            'name'     => $this->argument('name'),
            'domain'   => $this->argument('domain'),
            'username' => $this->argument('username'),
            'email'    => $this->argument('email'),
            'role'     => $this->option('role'),
            'guard'    => $this->option('guard'),
            'password' => $this->option('password'),
        ];

        if (blank($input['role'])) {
            $input['role'] = Config::string('vendra-permission.super_admin_role', 'super-admin');
        }

        if (blank($input['guard'])) {
            $input['guard'] = 'web';
        }

        if (blank($input['password'])) {
            $input['password'] = $this->input->isInteractive() ? $this->secret('Password') : null;
        }

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
            'password' => ['required', 'string'],
            'role'     => ['required', 'string', 'max:255'],
            'guard'    => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return null;
        }

        /** @var array{name: string, domain: string, username: string, email: string, password: string, role: string, guard: string} $data */
        $data = $validator->validated();

        return $data;
    }
}
