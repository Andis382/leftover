<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Every relationship this app uses is loaded on purpose. A closing
        // count draws thirty products and their history, and a lazy load
        // hiding inside a Blade loop is how that becomes two hundred queries.
        Model::preventLazyLoading(! app()->isProduction());
    }
}
