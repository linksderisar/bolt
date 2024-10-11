<?php

use Illuminate\Support\Facades\Route;
use LaraZeus\Bolt\Livewire\FillForms;


Route::domain(config('zeus-bolt.domain'))
    ->prefix(config('zeus-bolt.prefix'))
    ->name('bolt.')
    ->middleware(config('zeus-bolt.middleware'))
    ->group(function () {


        Route::get('{slug}/{extensionSlug?}', FillForms::class)
            ->name('form.show');
    });
