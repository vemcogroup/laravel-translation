<?php

namespace Vemcogroup\Translation;

use Illuminate\Support\ServiceProvider;
use Vemcogroup\Translation\Commands\Scan;
use Vemcogroup\Translation\Commands\Upload;
use Vemcogroup\Translation\Commands\Download;
use Vemcogroup\Translation\Commands\CreateJs;
use Vemcogroup\Translation\Commands\AddTerms;

class TranslationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/translation.php' => config_path('translation.php'),
        ], 'config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Scan::class,
                Upload::class,
                Download::class,
                CreateJs::class,
                AddTerms::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/translation.php', 'translation'
        );

        $this->app->singleton(Translation::class, function () {
            return new Translation();
        });
    }
}
