<?php

namespace App\Filament\Resources\Areas\RelationManagers;

use App\Filament\Resources\Modulos\ModuloResource;
use App\Filament\Support\ColumnasComunes;
use App\Models\Modulo;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Módulos de un área. Se crean y editan en un modal liviano (título, descripción y visibilidad);
 * las lecciones y el quiz se completan al abrir el módulo.
 */
class ModulosRelationManager extends RelationManager
{
    protected static string $relationship = 'modulos';

    protected static ?string $title = 'Módulos';

    protected static string|BackedEnum|null $icon = Heroicon::OutlinedRectangleStack;

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $cantidad = $ownerRecord->modulos()->count();

        return $cantidad > 0 ? (string) $cantidad : null;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextInput::make('titulo')
                    ->label('Título')
                    ->required()
                    ->maxLength(255)
                    ->autofocus(),
                Textarea::make('descripcion')
                    ->label('Descripción')
                    ->rows(3)
                    ->maxLength(500),
                Toggle::make('activo')
                    ->label('Visible para los colaboradores')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('titulo')
            ->modifyQueryUsing(fn ($query) => $query->withCount('lecciones')->withExists('quiz'))
            ->defaultSort('orden')
            ->reorderable('orden')
            ->paginated([10, 25])
            ->emptyStateIcon(Heroicon::OutlinedRectangleStack)
            ->emptyStateHeading('Aún no hay módulos')
            ->emptyStateDescription('Crea el primero; luego ábrelo para agregar sus lecciones y su quiz.')
            ->columns([
                TextColumn::make('titulo')
                    ->label('Módulo')
                    ->weight('semibold')
                    ->description(fn (Modulo $record): ?string => filled($record->descripcion) ? Str::limit($record->descripcion, 70) : null)
                    ->wrap(),
                TextColumn::make('lecciones_count')
                    ->label('Lecciones')
                    ->badge()
                    ->color('primary')
                    ->icon(Heroicon::OutlinedBookOpen)
                    ->alignCenter(),
                TextColumn::make('quiz_exists')
                    ->label('Quiz')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state ? 'Con quiz' : 'Sin quiz')
                    ->color(fn ($state): string => $state ? 'success' : 'gray')
                    ->icon(fn ($state) => $state ? Heroicon::OutlinedClipboardDocumentCheck : Heroicon::OutlinedMinus)
                    ->alignCenter(),
                ColumnasComunes::estado('activo', 'Activo', 'Inactivo'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Nuevo módulo')
                    ->icon(Heroicon::OutlinedPlus)
                    ->modalHeading('Nuevo módulo')
                    ->modalDescription('Solo lo básico. Después podrás agregarle lecciones y un quiz.')
                    ->modalWidth(Width::Large)
                    ->mutateDataUsing(function (array $data): array {
                        $data['orden'] = ((int) $this->getOwnerRecord()->modulos()->max('orden')) + 1;

                        return $data;
                    }),
            ])
            ->recordActions([
                Action::make('abrir')
                    ->label('Lecciones y quiz')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (Modulo $record): string => ModuloResource::getUrl('edit', ['record' => $record])),
                EditAction::make()
                    ->modalHeading('Editar módulo')
                    ->modalWidth(Width::Large),
                DeleteAction::make(),
            ]);
    }
}
