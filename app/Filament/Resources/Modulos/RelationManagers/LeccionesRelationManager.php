<?php

namespace App\Filament\Resources\Modulos\RelationManagers;

use App\Filament\Resources\Leccions\Schemas\LeccionForm;
use App\Filament\Support\ColumnasComunes;
use App\Filament\Support\VideoUnico;
use App\Models\Leccion;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Lecciones de un módulo: tabla paginada y un modal para crear o editar cada una.
 * El editor de texto y la subida de video solo se montan al abrir el modal.
 */
class LeccionesRelationManager extends RelationManager
{
    protected static string $relationship = 'lecciones';

    protected static ?string $title = 'Lecciones';

    protected static string|BackedEnum|null $icon = Heroicon::OutlinedAcademicCap;

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $cantidad = $ownerRecord->lecciones()->count();

        return $cantidad > 0 ? (string) $cantidad : null;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components(LeccionForm::camposAnidados());
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('titulo')
            ->defaultSort('orden')
            ->reorderable('orden')
            ->paginated([10, 25])
            ->emptyStateIcon(Heroicon::OutlinedAcademicCap)
            ->emptyStateHeading('Aún no hay lecciones')
            ->emptyStateDescription('Crea la primera; puedes agregar texto, un video y un PDF.')
            ->columns([
                TextColumn::make('titulo')
                    ->label('Lección')
                    ->weight('semibold')
                    ->wrap()
                    ->limit(80),
                TextColumn::make('duracion_min')
                    ->label('Duración')
                    ->suffix(' min')
                    ->placeholder('—')
                    ->alignCenter(),
                ColumnasComunes::tieneValor('archivo_video', 'Video subido', Heroicon::OutlinedFilm),
                ColumnasComunes::tieneValor('url_video', 'Video (enlace)', Heroicon::OutlinedVideoCamera),
                ColumnasComunes::tieneValor('archivo_pdf', 'PDF', Heroicon::OutlinedDocumentText),
                ColumnasComunes::estado('activa', 'Activa', 'Inactiva'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Nueva lección')
                    ->icon(Heroicon::OutlinedPlus)
                    ->modalHeading('Nueva lección')
                    ->modalDescription('Solo el título es obligatorio; el texto, el video y el PDF se pueden completar después.')
                    ->modalWidth(Width::FourExtraLarge)
                    ->mutateDataUsing(function (array $data): array {
                        $data = VideoUnico::normalizar($data);
                        $data['orden'] = ((int) $this->getOwnerRecord()->lecciones()->max('orden')) + 1;

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalHeading('Editar lección')
                    ->modalWidth(Width::FourExtraLarge)
                    ->mutateDataUsing(function (array $data, Leccion $record): array {
                        $data = VideoUnico::normalizar($data);

                        VideoUnico::borrarAnteriorSiCambio($record->archivo_video, $data['archivo_video'] ?? null);

                        return $data;
                    }),
                DeleteAction::make(),
            ]);
    }
}
