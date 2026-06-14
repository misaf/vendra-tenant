<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Laravel\Pennant\Concerns\HasFeatures;
use Misaf\VendraActivityLog\Concerns\HasDefaultActivityLogOptions;
use Misaf\VendraCurrency\Models\CurrencyCategory;
use Misaf\VendraCurrency\Traits\HasCurrency as CurrencyTrait;
use Misaf\VendraFaq\Models\Faq;
use Misaf\VendraFaq\Models\FaqCategory;
use Misaf\VendraLanguage\Models\Language;
use Misaf\VendraLanguage\Models\LanguageLine;
use Misaf\VendraPermission\Models\Permission;
use Misaf\VendraPermission\Models\Role;
use Misaf\VendraTenant\Database\Factories\TenantFactory;
use Misaf\VendraTransaction\Models\TransactionGateway;
use Misaf\VendraTransaction\Traits\HasTransaction;
use Misaf\VendraUser\Traits\HasUserProfile as UserProfileTrait;
use Misaf\VendraUser\Traits\HasUserProfileBalance as UserProfileBalanceTrait;
use Misaf\VendraUser\Traits\HasUserProfileDocument as UserProfileDocumentTrait;
use Misaf\VendraUser\Traits\HasUserProfilePhone as UserProfilePhoneTrait;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Multitenancy\Models\Tenant as SpatieTenant;
use Spatie\Sluggable\SlugOptions;
use Spatie\Tags\Tag;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;
use Znck\Eloquent\Traits\BelongsToThrough as TraitBelongsToThrough;

/**
 * @property int $id
 * @property string $name
 * @property string $description
 * @property string $slug
 * @property bool $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['name', 'description', 'slug', 'status'])]
final class Tenant extends SpatieTenant
{
    use HasDefaultActivityLogOptions;
    // use CurrencyTrait;
    /** @use HasFactory<TenantFactory> */
    use HasFactory;
    use HasFeatures;
    use HasRelationships;
    // use HasTransaction;
    use LogsActivity;
    use SoftDeletes;
    use TraitBelongsToThrough;
    // use UserProfileBalanceTrait;
    // use UserProfileDocumentTrait;
    // use UserProfilePhoneTrait;
    // use UserProfileTrait;

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory<static>
     */
    protected static function newFactory()
    {
        return TenantFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id'          => 'integer',
            'name'        => 'string',
            'description' => 'string',
            'slug'        => 'string',
            'status'      => 'boolean',
        ];
    }

    /**
     * @param Builder<Tenant> $query
     * @return Builder<Tenant>
     */
    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('status', true);
    }

    /**
     * @param Builder<Tenant> $query
     * @return Builder<Tenant>
     */
    public function scopeDisabled(Builder $query): Builder
    {
        return $query->where('status', false);
    }

    /**
     * @return HasMany<CurrencyCategory, $this>
     */
    public function currencyCategories(): HasMany
    {
        return $this->hasMany(CurrencyCategory::class);
    }

    /**
     * @return HasMany<FaqCategory, $this>
     */
    public function faqCategories(): HasMany
    {
        return $this->hasMany(FaqCategory::class);
    }

    /**
     * @return HasMany<Faq, $this>
     */
    public function faqs(): HasMany
    {
        return $this->hasMany(Faq::class);
    }

    // public function languageLines(): HasMany
    // {
    //     return $this->hasMany(LanguageLine::class);
    // }

    /**
     * @return HasMany<Language, $this>
     */
    public function languages(): HasMany
    {
        return $this->hasMany(Language::class);
    }

    /**
     * @return HasMany<Permission, $this>
     */
    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class);
    }

    /**
     * @return HasMany<Role, $this>
     */
    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    /**
     * @return HasMany<Tag, $this>
     */
    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    /**
     * @return HasMany<TenantDomain, $this>
     */
    public function tenantDomains(): HasMany
    {
        return $this->hasMany(TenantDomain::class);
    }

    /**
     * @return HasMany<TransactionGateway, $this>
     */
    public function transactionGateways(): HasMany
    {
        return $this->hasMany(TransactionGateway::class);
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->preventOverwrite();
    }
}
