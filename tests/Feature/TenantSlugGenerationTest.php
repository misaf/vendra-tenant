<?php

declare(strict_types=1);

use Misaf\VendraTenant\Models\Tenant;

it('generates a slug from the name when none is provided', function (): void {
    $tenant = Tenant::factory()->create([
        'name' => 'Hello World',
        'slug' => null,
    ]);

    expect($tenant->slug)->toBe('hello-world');
});

it('keeps a manually provided slug and never overwrites it', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'custom-slug']);

    $tenant->update(['name' => 'Changed Name']);

    expect($tenant->refresh()->slug)->toBe('custom-slug');
});
