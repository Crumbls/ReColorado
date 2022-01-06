<?php

namespace Crumbls\ReColorado;

use Crumbls\ReColorado\Commands\ImportAgent;
use Crumbls\ReColorado\Commands\ImportAgentsByOffice;
use Crumbls\ReColorado\Commands\ImportOffice;
use Crumbls\ReColorado\Commands\TestA;
use Crumbls\ReColorado\Facades\ReColorado;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ReColoradoServiceProvider extends ServiceProvider
{
    public function boot()
    {
//        echo __METHOD__;exit;
        $this->commands([
            TestA::class, // Removable
            ImportAgent::class,
            ImportOffice::class,
            ImportAgentsByOffice::class
        ]);
        return;
        /**
         * Active Brokerage Admin
         * - Sortable
         * - Toggle active/inactive
         * - Subscription
         * - Subscription expiration
         * - Created Date
         * - Actions
         * -- View
         * -- Edit
         * -- Impersonate
         * -- Reset Password Email
         * -- Delete
         * - Create Brokerage Firm
         * - Create TC Admin
         * Brokerage Admin Dashboard
         * -- All settings from the admin dashboard for the brokerage
         * -- Add Agent
         * -- Agent Actions
         * --- View
         * --- Edit
         * --- Reset PW
         * --- Impersonate
         * --- Delete
         *
         */
        // ... other things
        $this->bootRoutes();
        $this->loadViewsFrom(__DIR__ . '/Views', 'admin');


        $this->loadViewComponentsAs('admin', [
            BrokerageTable::class,
            UserTable::class
        ]);
        return;
        $this->loadViewComponentsAs('admin', [
            ClientActive::class,
            ClientNew::class,
            ContractUnder::class,
            ListingActive::class,
            TransactionClosed::class
        ]);
        return;
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/laravel-awesome-package.php' => config_path('laravel-awesome-package.php'),
            ], 'config');

            $this->publishes([
                __DIR__ . '/../resources/views' => base_path('resources/views/vendor/laravel-awesome-package'),
            ], 'views');

            $migrationFileName = 'create_package_table.php';
            if (!$this->migrationFileExists($migrationFileName)) {
                $this->publishes([
                    __DIR__ . "/../database/migrations/{$migrationFileName}.stub" => database_path('migrations/' . date('Y_m_d_His', time()) . '_' . $migrationFileName),
                ], 'migrations');
            }
        }

    }

    protected function bootRoutes() : void {
        Route::group([
            'prefix' => 'admin',
            'as' => 'admin.',
            'middleware' => ['web','role:admin']
        ], function() {
            $this->loadRoutesFrom(__DIR__.'/Routes/web.php');
        });



    }

    public static function migrationFileExists(string $migrationFileName): bool
    {
        $len = strlen($migrationFileName);
        foreach (glob(database_path("migrations/*.php")) as $filename) {
            if ((substr($filename, -$len) === $migrationFileName)) {
                return true;
            }
        }

        return false;
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/Config/config.php', 'recolorado');

        $this->app->bind('recolorado', function($app) {
            return new Client();
        });
        $loader = \Illuminate\Foundation\AliasLoader::getInstance();
        $loader->alias('ReColorado', ReColorado::class);
    }
}