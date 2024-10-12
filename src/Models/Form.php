<?php

namespace LaraZeus\Bolt\Models;

use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use LaraZeus\Bolt\Concerns\HasActive;
use LaraZeus\Bolt\Concerns\HasUpdates;
use LaraZeus\Bolt\Database\Factories\FormFactory;
use LaraZeus\Bolt\Facades\Extensions;
use Spatie\Translatable\HasTranslations;

/**
 * @property string $updated_at
 * @property int $is_active
 * @property string $desc
 * @property string $name
 * @property string $slug
 * @property string $description
 * @property array $options
 * @property string $extensions
 * @property mixed $sections
 * @property mixed $fields
 * @property int $user_id
 * @property int $ordering
 */
class Form extends Model
{
    use HasActive;
    use HasFactory;
    use HasTranslations;
    use HasUpdates;
    use SoftDeletes;

    public array $translatable = ['name', 'description', 'details'];

    protected $guarded = [];

    protected $appends = [
        'slug_url',
    ];

    protected $casts = [
        'options' => 'array',
        'user_id' => 'integer',
    ];

    public function getTable(): string
    {
        return config('zeus-bolt.table-prefix') . 'forms';
    }

    protected static function booted(): void
    {
        static::deleting(function (Form $form) {
            $canDelete = Extensions::init($form, 'canDelete', []);

            if ($canDelete === null) {
                $canDelete = true;
            }

            if (! $canDelete) {
                Notification::make()
                    ->title(__('Can\'t delete a form linked to an Extensions'))
                    ->danger()
                    ->send();

                return false;
            }

            if ($form->isForceDeleting()) {
                $form->fieldsResponses()->withTrashed()->get()->each(fn ($item) => $item->forceDelete());
                $form->responses()->withTrashed()->get()->each(fn ($item) => $item->forceDelete());
                $form->sections()->withTrashed()->get()->each(function ($item) {
                    $item->fields()->withTrashed()->get()->each(fn ($item) => $item->forceDelete());
                    $item->forceDelete();
                });
            } else {
                $form->fieldsResponses->each(fn ($item) => $item->delete());
                $form->responses->each(fn ($item) => $item->delete());
                $form->sections->each(function ($item) {
                    $item->fields->each(fn ($item) => $item->delete());
                    $item->delete();
                });
            }

            return true;
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function newFactory(): Factory
    {
        return FormFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('zeus-bolt.models.User') ?? config('auth.providers.users.model'));
    }


    /** @phpstan-return hasMany<Section> */
    public function sections(): HasMany
    {
        return $this->hasMany(config('zeus-bolt.models.Section'));
    }

    /** @phpstan-return hasManyThrough<Field> */
    public function fields(): HasManyThrough
    {
        return $this->hasManyThrough(config('zeus-bolt.models.Field'), config('zeus-bolt.models.Section'));
    }

    /** @phpstan-return hasMany<Response> */
    public function responses(): hasMany
    {
        return $this->hasMany(config('zeus-bolt.models.Response'));
    }

    /** @phpstan-return hasMany<FieldResponse> */
    public function fieldsResponses(): HasMany
    {
        return $this->hasMany(config('zeus-bolt.models.FieldResponse'));
    }




    protected function slugUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getUrl(),
        );
    }

    public function getUrl(): string | array
    {
        if ($this->extensions === null) {
            return route('bolt.form.show', ['slug' => $this->slug]);
        }

        return collect(Extensions::init($this, 'getItems', ['form' => $this]))
            ->mapWithKeys(function ($key, $item) {
                return [
                    $key => [
                        'label' => $key,
                        'url' => Extensions::init($this, 'getUrl', ['slug' => $item]),
                    ],
                ];
            })
            ->toArray();
    }
}
