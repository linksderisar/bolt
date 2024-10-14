@php
    $colors = \Illuminate\Support\Arr::toCssStyles([
        \Filament\Support\get_color_css_variables($zeusForm->options['primary_color'] ?? 'primary', shades: [50, 100, 200, 300, 400, 500, 600, 700, 800, 900]),
    ]);
@endphp

<div class="not-prose" style="{{ $colors }}">

    @if(!$inline)
        <x-slot name="header">
            <h2>{{ $zeusForm->name ?? '' }}</h2>
            <p class="text-gray-400 text-mdd my-2">{{ $zeusForm->description ?? '' }}</p>
        </x-slot>

        <x-slot name="breadcrumbs">
            <li class="flex items-center">
                {{ $zeusForm->name }}
            </li>
        </x-slot>
    @endif

    @if(!$inline)
        @include($boltTheme.'.loading')
    @endif



    @if($sent)
        @include($boltTheme.'.submitted')
    @else
        <x-filament-panels::form wire:submit.prevent="store" :class="!$inline ? 'mx-2' : ''">
            @if(!$inline)
                {{ \LaraZeus\Bolt\Facades\Bolt::renderHookBlade('zeus-form.before') }}
            @endif

            {!! \LaraZeus\Bolt\Facades\Extensions::init($zeusForm, 'render',$extensionData) !!}

            @if(!empty($zeusForm->details))
                <div class="m-4">
                    <x-filament::section :compact="true">
                        {!! nl2br($zeusForm->details) !!}
                    </x-filament::section>
                </div>
            @endif

            {{ $this->form }}

            <div class="px-4 py-2 text-center">
                <x-filament::button
                    form="store"
                    type="submit"
                    :color="$zeusForm->options['primary_color'] ?? 'primary'"
                >
                    {{ __('Save') }}
                </x-filament::button>
            </div>

            @if(!$inline)
                {{ \LaraZeus\Bolt\Facades\Bolt::renderHookBlade('zeus-form.after') }}
            @endif
        </x-filament-panels::form>

        <x-filament-actions::modals/>
    @endif
</div>