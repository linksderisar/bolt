<?php

namespace LaraZeus\Bolt\Livewire;

use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use LaraZeus\Bolt\Concerns\Designer;
use LaraZeus\Bolt\Events\FormMounted;
use LaraZeus\Bolt\Events\FormSent;
use LaraZeus\Bolt\Facades\Extensions;
use LaraZeus\Bolt\Models\Form;
use Livewire\Component;

/**
 * @property mixed $form
 */
class PreviewForm extends FillForms
{
    public function store(): void
    {
        $this->validate();
    }
}
