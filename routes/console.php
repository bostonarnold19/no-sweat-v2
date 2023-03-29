<?php

use Illuminate\Support\Facades\Artisan;
use App\Services\ModuleGeneratorService;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('install:modules', function () {
    $modules = config('project-modules');
    foreach($modules as $module => $models) {
        foreach($models as $model) {
            (new ModuleGeneratorService())->generate($module, $model);
            $this->comment("MODULE: {$module} -- {$model}");
        }
    }
    $this->comment('DONE INSTALLATION!');
})->purpose('Install Project Modules');
