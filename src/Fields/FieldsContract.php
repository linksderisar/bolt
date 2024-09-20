<?php

namespace LaraZeus\Bolt\Fields;

use Filament\Actions\Exports\ExportColumn;
use Filament\Forms\Get;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use LaraZeus\Bolt\BoltPlugin;
use LaraZeus\Bolt\Concerns\HasHiddenOptions;
use LaraZeus\Bolt\Concerns\HasOptions;
use LaraZeus\Bolt\Contracts\Fields;
use LaraZeus\Bolt\Facades\Bolt;
use LaraZeus\Bolt\Models\Field;
use LaraZeus\Bolt\Models\FieldResponse;
use LaraZeus\Bolt\Models\Response;

/** @phpstan-return Arrayable<string,mixed> */
abstract class FieldsContract implements Arrayable, Fields
{
    use HasHiddenOptions;
    use HasOptions;

    public bool $disabled = false;

    public string $renderClass;

    public int $sort;

    public function toArray(): array
    {
        return [
            'disabled' => $this->disabled,
            'class' => '\\' . get_called_class(),
            'renderClass' => $this->renderClass,
            'hasOptions' => $this->hasOptions(),
            'code' => class_basename($this),
            'sort' => $this->sort,
            'title' => $this->title(),
            'description' => $this->description(),
            'icon' => $this->icon(),
        ];
    }

    public function title(): string
    {
        return __(class_basename($this));
    }

    public function description(): string
    {
        return __('field text for all the text you need');
    }

    public function icon(): string
    {
        return 'iconpark-aligntextcenter-o';
    }

    public function hasOptions(): bool
    {
        return method_exists(get_called_class(), 'getOptions');
    }

    public function getResponse(Field $field, FieldResponse $resp): string
    {
        return $resp->response;
    }

    // @phpstan-ignore-next-line
    public function appendFilamentComponentsOptions($component, $zeusField, bool $hasVisibility = false)
    {
        if (is_string($zeusField->options)) {
            $zeusField->options = json_decode($zeusField->options, true);
        }

        $htmlId = $zeusField->options['htmlId'] ?? str()->random(6);

        $component
            ->label($zeusField->name)
            ->id($htmlId)
            ->helperText($zeusField->description);

        if (optional($zeusField->options)['is_required']) {
            $component = $component->required();
        }

        if (request()->filled($htmlId)) {
            $component = $component->default(request($htmlId));
        }

        if (optional($zeusField->options)['column_span_full']) {
            $component = $component->columnSpanFull();
        }

        if (optional($zeusField->options)['hint']) {
            if (optional($zeusField->options)['hint']['text']) {
                $component = $component->hint($zeusField->options['hint']['text']);
            }
            if (optional($zeusField->options)['hint']['icon']) {
                $component = $component->hintIcon($zeusField->options['hint']['icon'], tooltip: $zeusField->options['hint']['icon-tooltip'] ?? $zeusField->options['hint']['text']);
            }
            if (optional($zeusField->options)['hint']['color']) {
                $component = $component->hintColor(fn () => Color::hex($zeusField->options['hint']['color']));
            }
        }

        $component = $component
            ->visible(function ($record, Get $get) use ($zeusField) {
                if (! isset($zeusField->options['visibility']) || ! $zeusField->options['visibility']['active']) {
                    return true;
                }

                $relatedField = $zeusField->options['visibility']['fieldID'];
                $relatedFieldValues = $zeusField->options['visibility']['values'];

                if (empty($relatedField) || empty($relatedFieldValues)) {
                    return true;
                }

                $relatedFieldArray = Arr::wrap($get('zeusData.' . $relatedField));
                if (in_array($relatedFieldValues, $relatedFieldArray)) {
                    return true;
                }

                return false;
            });

        if ($hasVisibility) {
            return $component->live();
        }

        return $component;
    }

    public function getCollectionsValuesForResponse(Field $field, FieldResponse $resp): string
    {
        $response = $resp->response;

        if (blank($response)) {
            return '';
        }

        if (Bolt::isJson($response)) {
            $response = json_decode($response);
        }

        $response = Arr::wrap($response);

        $dataSource = (int) $field->options['dataSource'];
        $cacheKey = 'dataSource_' . $dataSource . '_response_' . md5(serialize($response));

        $response = Cache::remember($cacheKey, config('zeus-bolt.cache.collection_values'), function () use ($field, $response, $dataSource) {

            // Handle case when dataSource is from the default model: `Collection`
            if ($dataSource !== 0) {
                return BoltPlugin::getModel('Collection')::query()
                    ->find($dataSource)
                    ?->values
                    ->whereIn('itemKey', $response)
                    ->pluck('itemValue')
                    ->join(', ') ?? '';
            }

            // Handle case when dataSource is custom model class
            if (class_exists($field->options['dataSource'])) {
                $dataSourceClass = app($field->options['dataSource']);

                return $dataSourceClass->getQuery()
                    ->whereIn($dataSourceClass->getKeysUsing(), $response)
                    ->pluck($dataSourceClass->getValuesUsing())
                    ->join(', ');
            }

            return '';
        });

        return (is_array($response)) ? implode(', ', $response) : $response;
    }

    //@phpstan-ignore-next-line
    public static function getFieldCollectionItemsList(Field  | array $zeusField): Collection | array
    {
        if (is_array($zeusField)) {
            $zeusField = (object) $zeusField;
        }

        $getCollection = collect();

        //@phpstan-ignore-next-line
        if (optional($zeusField->options)['dataSource'] === null) {
            return $getCollection;
        }

        if (class_exists($zeusField->options['dataSource'])) {
            //@phpstan-ignore-next-line
            $dataSourceClass = new $zeusField->options['dataSource'];
            $getCollection = $dataSourceClass->getQuery()->pluck(
                $dataSourceClass->getValuesUsing(),
                $dataSourceClass->getKeysUsing()
            );
        }

        return $getCollection;
    }

    public function TableColumn(Field $field): ?Column
    {
        return TextColumn::make('zeusData.' . $field->id)
            ->label($field->name)
            ->sortable(false)
            ->searchable(query: function (Builder $query, string $search): Builder {
                return $query
                    ->whereHas('fieldsResponses', function ($query) use ($search) {
                        $query->where('response', 'like', '%' . $search . '%');
                    });
            })
            ->getStateUsing(fn (Response $record) => $this->getFieldResponseValue($record, $field))
            ->html()
            ->toggleable();
    }

    public function ExportColumn(Field $field): ?ExportColumn
    {
        return ExportColumn::make('zeusData.' . $field->options['htmlId'])
            ->label($field->name)
            ->state(function (Response $record) use ($field) {

                $response = $record->fieldsResponses()->where('field_id', $field->id)->first();
                if ($response === null) {
                    return '-';
                }
                if (Bolt::isJson($response->response)) {
                    return json_decode($response->response, true);
                }

                return $response->response;
            });
    }

    public function getFieldResponseValue(Response $record, Field $field): string
    {
        $fieldResponse = $record->fieldsResponses->where('field_id', $field->id)->first();
        if ($fieldResponse === null) {
            return '';
        }

        return (new $field->type)->getResponse($field, $fieldResponse);
    }

    public function entry(Field $field, FieldResponse $resp): string
    {
        return $this->getResponse($field, $resp);
    }
}
