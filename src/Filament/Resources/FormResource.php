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
            ->columns([
                TextColumn::make('id')->sortable()->label(__('Form ID'))->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name')->searchable()->sortable()->label(__('Form Name'))->toggleable(),
                TextColumn::make('slug')->searchable()->sortable()->label(__('Form Slug'))->toggleable(),
            ])
            ->actions(static::getActions())
            ->filters([
                TrashedFilter::make(),
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
            EditAction::make('edit'),
            ReplicateFormAction::make(),
            RestoreAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
        ];

        return [
            Action::make('preview')
            ->label(__('Preview'))
            ->icon('heroicon-o-arrow-top-right-on-square')
            ->tooltip(__('Feel free to click save in the form, it will show only the validation messages.'))
            ->url(fn ($record) => route('bolt.form.show', $record))
            ->openUrlInNewTab(),
            ActionGroup::make($actions)
        ];
    }


}
