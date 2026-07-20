<?php

declare(strict_types=1);

arch()->preset()->php();
arch()->preset()->security();
arch()->preset()->laravel();

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
