<?php

namespace LaraZeus\Bolt\Fields\Classes;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput as TextInputAlias;
use Filament\Forms\Get;
use Filament\Support\Colors\Color;
use Guava\FilamentIconPicker\Forms\IconPicker;
use Illuminate\Support\HtmlString;
use LaraZeus\Accordion\Forms\Accordion;
use LaraZeus\Accordion\Forms\Accordions;
use LaraZeus\Bolt\Facades\Bolt;
use LaraZeus\Bolt\Fields\FieldsContract;
use Stevebauman\Purify\Facades\Purify;

class Description extends FieldsContract
{
    public string $renderClass = Placeholder::class;

    public int $sort = 1;

    public function title(): string
    {
        return __('Description');
    }

    public function icon(): string
    {
        return 'tabler-text-size';
    }

    public function description(): string
    {
        return __('This will render a description text');
    }

    public static function hasDescriptionFields(): bool
    {
        return false;
    }

    public static function getOptions(?array $sections = null, ?array $field = null): array
    {
        return [
            RichEditor::make('options.text')->toolbarButtons([
                'bold',
                'bulletList',
                'h2',
                'h3',
                'italic',
                'link',
                'orderedList',
            ])
            ];
    }

    public static function getOptionsHidden(): array
    {
        return [
            Hidden::make('options.text'),
        ];
    }

    // @phpstan-ignore-next-line
    /** @param Placeholder $component */
    public function appendFilamentComponentsOptions($component, $zeusField, bool $hasVisibility = false)
    {
        parent::appendFilamentComponentsOptions($component, $zeusField, $hasVisibility);

        if($zeusField->options['text'] ?? false) {
            $allowedOptions = ['HTML.Allowed' => 'div,b,i,a[href],ul,ol,li,h2,h3'];

            $component->content(fn() => new HtmlString(
                '<div class="prose prose-sm">' . Purify::clean($zeusField->options['text'], $allowedOptions) . '</div>'
            ));
        }


        return $component;
    }
}
