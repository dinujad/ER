<?php

namespace Modules\Accounting\Providers;

use Illuminate\Support\ServiceProvider;

class AccountingServiceProvider extends ServiceProvider
{
    protected $defer = false;

    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadHelpers();
    }

    public function register()
    {
        $this->loadRoutesFrom(__DIR__ . '/../Http/routes.php');
    }

    protected function registerConfig()
    {
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('accounting.php'),
        ], 'config');
        $this->mergeConfigFrom(__DIR__.'/../Config/config.php', 'accounting');
    }

    public function registerViews()
    {
        $viewPath = resource_path('views/modules/accounting');
        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([$sourcePath => $viewPath], 'views');

        $this->loadViewsFrom(array_merge(
            array_map(fn ($path) => $path . '/modules/accounting', \Config::get('view.paths')),
            [$sourcePath]
        ), 'accounting');
    }

    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/accounting');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'accounting');
        } else {
            $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'accounting');
        }
    }

    protected function loadHelpers()
    {
        $helperFile = __DIR__ . '/../Helpers/general_helper.php';
        if (file_exists($helperFile)) {
            require_once $helperFile;
        }
    }

    public function provides()
    {
        return [];
    }
}
