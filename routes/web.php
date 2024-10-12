<?php

use Illuminate\Support\Facades\Route;
use LaraZeus\Bolt\Livewire\FillForms;
use LaraZeus\Bolt\Livewire\PreviewForm;


Route::domain(config('zeus-bolt.domain'))
    ->prefix(config('zeus-bolt.prefix'))
    ->name('bolt.')
    ->middleware(config('zeus-bolt.middleware'))
    ->group(function () {
        Route::get('{slug}/{extensionSlug?}', PreviewForm::class)
            ->name('form.show');
    });
