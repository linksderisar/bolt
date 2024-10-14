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
class FillForms extends Component implements Forms\Contracts\HasForms
{
    use Designer;
    use InteractsWithForms;

    public Form $zeusForm;

    public array $extensionData;

    public array $zeusData = [];

    public bool $sent = false;

    public bool $inline = false;

    protected static ?string $boltFormDesigner = null;

    public function getBoltFormDesigner(): ?string
    {
        return static::$boltFormDesigner;
    }

    public static function getBoltFormDesignerUsing(?string $form): void
    {
        static::$boltFormDesigner = $form;
    }

    protected function getFormSchema(): array
    {
        $getDesignerClass = $this->getBoltFormDesigner() ?? Designer::class;

        return $getDesignerClass::ui($this->zeusForm, $this->inline);
    }

    protected function getFormModel(): Form
    {
        return $this->zeusForm;
    }

    /**
     * @throws \Throwable
     */
    public function mount(
        mixed $slug,
        mixed $extensionSlug = null,
        mixed $extensionData = [],
        mixed $inline = false,
    ): void {
        $this->inline = $inline;

        $this->zeusForm = config('zeus-bolt.models.Form')::query()
            ->with(['fields', 'sections.fields'])
            ->where('slug', $slug)
            ->firstOrFail();

        $this->extensionData = Extensions::init($this->zeusForm, 'canView', ['extensionSlug' => $extensionSlug, 'extensionData' => $extensionData]) ?? [];

        foreach ($this->zeusForm->fields as $field) {
            $this->zeusData[$field->id] = '';
        }

        $this->form->fill();

        event(new FormMounted($this->zeusForm));
    }

    public function store(): void
    {
        $this->validate();

        Extensions::init($this->zeusForm, 'preStore', $this->extensionData);

        $response = config('zeus-bolt.models.Response')::create([
            'form_id' => $this->zeusForm->id,
            'user_id' => (auth()->check()) ? auth()->user()->id : null,
            'status' => 'NEW',
            'notes' => '',
        ]);

        $fieldsData = Arr::except($this->form->getState()['zeusData'], 'extensions');

        foreach ($fieldsData as $field => $value) {
            $setValue = $value;

            if (! empty($setValue) && is_array($setValue)) {
                $value = json_encode($value);
            }
            config('zeus-bolt.models.FieldResponse')::create([
                'response' => (! empty($value)) ? $value : '',
                'response_id' => $response->id,
                'form_id' => $this->zeusForm->id,
                'field_id' => $field,
            ]);
        }

        event(new FormSent($response));

        $this->extensionData['response'] = $response;
        $this->extensionData['extensionsComponent'] = $this->form->getState()['zeusData']['extensions'] ?? [];

        $extensionItemId = Extensions::init($this->zeusForm, 'store', $this->extensionData) ?? [];
        $this->extensionData['extInfo'] = $extensionItemId;

        $response->update(['extension_item_id' => $extensionItemId['itemId'] ?? null]);



        $this->sent = true;
    }

    public function render(): View
    {
        $view = app('boltTheme') . '.fill-forms';

        if ($this->inline) {
            return view($view);
        }

        return view($view)->layout(config('zeus-bolt.layout'));
    }
}
