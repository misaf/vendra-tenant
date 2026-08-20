<?php

declare(strict_types=1);

arch()->preset()->php();
arch()->preset()->security();
arch()->preset()->laravel();

/*
 | The engine is a mechanism, not a business domain. It must not reach for the
 | ecommerce Store that plays the tenant role here, the reseller that owns
 | stores, or the console that administers them — otherwise "configurable
 | tenant model" is a claim the code does not keep.
 */
arch('the tenancy engine does not depend on the business that uses it')
    ->expect('Misaf\VendraTenant')->not->toUse([
        'Misaf\VendraStore',
        'Misaf\VendraReseller',
        'Misaf\VendraConsole',
        'Misaf\VendraSubscription',
    ]);

arch('the tenant provider does not depend on domain modules')
    ->expect('Misaf\VendraTenant')->not->toUse([
        'Misaf\VendraProduct',
        'Misaf\VendraBlog',
        'Misaf\VendraCart',
        'Misaf\VendraAttribute',
        'Misaf\VendraCurrency',
        'Misaf\VendraTransaction',
        'Misaf\VendraNewsletter',
        'Misaf\VendraFaq',
        'Misaf\VendraCustomPage',
        'Misaf\VendraAffiliate',
        'Misaf\VendraMultimedia',
        'Misaf\VendraTagger',
        'Misaf\VendraLanguage',
        'Misaf\VendraUser',
        'Misaf\VendraPermission',
        'Misaf\VendraSocialite',
        'Misaf\VendraAuthifyLog',
        'Misaf\VendraActivityLog',
        'Misaf\VendraDeveloperLogins',
        'Misaf\VendraVerification',
        'Misaf\VendraAddress',
        'Misaf\VendraDocument',
        'Misaf\VendraPhone',
        'Misaf\VendraUserProfile',
    ]);
