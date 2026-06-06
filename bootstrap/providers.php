<?php

use App\Providers\AppServiceProvider;
use App\Providers\FastPaginateServiceProvider;
use App\Providers\FolioServiceProvider;
use App\Providers\FortifyServiceProvider;

return [
    AppServiceProvider::class,
    FolioServiceProvider::class,
    FortifyServiceProvider::class,
    FastPaginateServiceProvider::class,
];
