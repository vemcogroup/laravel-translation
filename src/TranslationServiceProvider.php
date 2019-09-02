<?php

namespace Vemcogroup\Translation;

use Illuminate\Support\ServiceProvider;
use Vemcogroup\Translation\Commands\Scan;
use Vemcogroup\Translation\Commands\Upload;
use Vemcogroup\Translation\Commands\Download;
use Vemcogroup\Translation\Commands\CreateJs;

class TranslationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/translations.php' => config_path('translations.php'),
        ], 'config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Scan::class,
                Upload::class,
                Download::class,
                CreateJs::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/translations.php', 'translations'
        );

        $this->app->singleton(Translation::class, function () {
            return new Translation();
        });
    }
}
