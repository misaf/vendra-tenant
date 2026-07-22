<?php

declare(strict_types=1);

use Illuminate\Support\Uri;

return [
    'central_host' => Uri::of((string) env('APP_URL', 'http://localhost'))->host(),
];
