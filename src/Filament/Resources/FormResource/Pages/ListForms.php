<?php

namespace LaraZeus\Bolt\Filament\Resources\FormResource\Pages;

use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use LaraZeus\Bolt\Facades\Bolt;
use LaraZeus\Bolt\Filament\Resources\FormResource;

class ListForms extends ListRecords
{
    use ListRecords\Concerns\Translatable;

    protected static string $resource = FormResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [
            Actions\LocaleSwitcher::make(),
            Actions\CreateAction::make('create'),

        ];

        if (Bolt::hasPro()) {
            //@phpstan-ignore-next-line
            $actions[] = \LaraZeus\BoltPro\Actions\PresetAction::make('new from preset')
                ->visible(config('zeus-bolt.show_presets'));
        }

        return $actions;
    }
}
