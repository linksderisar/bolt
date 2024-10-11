<?php

namespace LaraZeus\Bolt\Filament\Resources;

use Closure;
use Filament\Forms\Form;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use LaraZeus\Bolt\BoltPlugin;
use LaraZeus\Bolt\Concerns\HasOptions;
use LaraZeus\Bolt\Concerns\Schemata;
use LaraZeus\Bolt\Enums\Resources;
use LaraZeus\Bolt\Facades\Bolt;
use LaraZeus\Bolt\Filament\Actions\ReplicateFormAction;
use LaraZeus\Bolt\Filament\Resources\FormResource\Pages;
use LaraZeus\Bolt\Models\Form as ZeusForm;
use LaraZeus\ListGroup\Infolists\ListEntry;

class FormResource extends BoltResource
{
    use HasOptions;
    use Schemata;

    protected static ?string $navigationIcon = 'clarity-form-line';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    protected static Closure | array | null $boltFormSchema = null;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getModel(): string
    {
        return BoltPlugin::getModel('Form');
    }

    public static function getNavigationBadge(): ?string
    {
        if (! BoltPlugin::getNavigationBadgesVisibility(Resources::FormResource)) {
            return null;
        }

        return (string) BoltPlugin::getModel('Form')::query()->count();
    }

    public static function getModelLabel(): string
    {
        return __('Form');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Forms');
    }

    public static function getNavigationLabel(): string
    {
        return __('Forms');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make()->schema([
                    TextEntry::make('name')
                        ->label(__('name')),

                    ListEntry::make('items')
                        ->visible(fn (ZeusForm $record) => $record->extensions !== null)
                        ->heading(__('Form Links'))
                        ->list()
                        ->state(fn ($record) => $record->slug_url),

                    TextEntry::make('slug')
                        ->label(__('slug'))
                        ->url(fn (ZeusForm $record) => route('bolt.form.show', ['slug' => $record->slug]))
                        ->visible(fn (ZeusForm $record) => $record->extensions === null)
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->openUrlInNewTab(),

                    TextEntry::make('description')
                        ->label(__('description')),
                    IconEntry::make('is_active')
                        ->label(__('is active'))
                        ->icon(fn (string $state): string => match ($state) {
                            '0' => 'clarity-times-circle-solid',
                            default => 'clarity-check-circle-line',
                        })
                        ->color(fn (string $state): string => match ($state) {
                            '0' => 'warning',
                            '1' => 'success',
                            default => 'gray',
                        }),

                    TextEntry::make('start_date')
                        ->label(__('start date'))
                        ->dateTime(),
                    TextEntry::make('end_date')
                        ->label(__('end date'))
                        ->dateTime(),
                ])
                    ->columns(),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema(static::$boltFormSchema ?? static::getMainFormSchema());
    }

    public function getBoltFormSchema(): array | Closure | null
    {
        return static::$boltFormSchema;
    }

    public static function getBoltFormSchemaUsing(array | Closure | null $form): void
    {
        static::$boltFormSchema = $form;
    }

    /**
     * @throws \Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('ordering')
            ->columns([
                TextColumn::make('id')->sortable()->label(__('Form ID'))->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name')->searchable()->sortable()->label(__('Form Name'))->toggleable(),
                TextColumn::make('category.name')->searchable()->label(__('Category'))->sortable()->toggleable(),
                IconColumn::make('is_active')->boolean()->label(__('Is Active'))->sortable()->toggleable(),
                TextColumn::make('start_date')->dateTime()->searchable()->sortable()->label(__('Start Date'))->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('end_date')->dateTime()->searchable()->sortable()->label(__('End Date'))->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('responses_exists')->boolean()->exists('responses')->label(__('Responses Exists'))->sortable()->toggleable()->searchable(false),
                TextColumn::make('responses_count')->counts('responses')->label(__('Responses Count'))->sortable()->toggleable()->searchable(false),
            ])
            ->actions(static::getActions())
            ->filters([
                TrashedFilter::make(),
                Filter::make('is_active')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true))
                    ->label(__('Is Active')),

                Filter::make('not_active')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where('is_active', false))
                    ->label(__('Inactive')),


            ])
            ->bulkActions([
                DeleteBulkAction::make(),
                ForceDeleteBulkAction::make(),
                RestoreBulkAction::make(),
            ]);
    }

    /** @phpstan-return Builder<ZeusForm> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListForms::route('/'),
            'create' => Pages\CreateForm::route('/create'),
            'edit' => Pages\EditForm::route('/{record}/edit'),
            'view' => Pages\ViewForm::route('/{record}'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            FormResource\Widgets\FormOverview::class,
        ];
    }

    public static function getActions(): array
    {
        $actions = [
            ViewAction::make(),
            EditAction::make('edit'),
            ReplicateFormAction::make(),
            RestoreAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
        ];

        return [ActionGroup::make($actions)];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        $formNavs = [
            Pages\ViewForm::class,
            Pages\EditForm::class,
        ];

        return $page->generateNavigationItems($formNavs);
    }
}
