<?php
/**
 * Created by PhpStorm.
 * User: leodanielstuder
 * Date: 01.06.19
 * Time: 14:22
 */

namespace le0daniel\LaravelResumableJs\Providers;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use le0daniel\LaravelResumableJs\Upload\UploadService;

class ResumableJsProvider extends ServiceProvider
{

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/resumablejs.php', 'resumablejs'
        );
    }

    public function boot(){

        /* Publish the config */
        $this->publishes([
            __DIR__.'/../config/resumablejs.php' => config_path('resumablejs.php'),
        ]);

        /* Register Routes */
        $this->loadRoutesFrom(__DIR__.'/../routes/upload.php');

        /* Add migrations */
        $this->loadMigrationsFrom(__DIR__.'/../migrations');
    }

}
