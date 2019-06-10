<?php
/**
 * Created by PhpStorm.
 * User: leodanielstuder
 * Date: 01.06.19
 * Time: 14:22
 */

namespace le0daniel\Laravel\ResumableJs\Providers;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use le0daniel\Laravel\ResumableJs\Upload\Manager;

class ResumableJsProvider extends ServiceProvider
{

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/resumablejs.php', 'resumablejs'
        );
    }

    public function boot(){

        /* Register Routes */
        $this->loadRoutesFrom(__DIR__.'/../routes/upload.php');

        /* Add migrations */
        $this->loadMigrationsFrom(__DIR__.'/../migrations');
    }

}