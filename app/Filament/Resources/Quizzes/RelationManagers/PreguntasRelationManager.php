<?php

namespace App\Filament\Resources\Quizzes\RelationManagers;

use App\Filament\Resources\Preguntas\Schemas\PreguntaForm;
use App\Models\Pregunta;
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
 * Preguntas de un quiz: tabla paginada y un modal para crear o editar cada pregunta con sus opciones.
 * El formulario solo se monta al abrir el modal, así que la página queda liviana aunque haya muchas.
 */
class PreguntasRelationManager extends RelationManager
{
    protected static string $relationship = 'preguntas';

    protected static ?string $title = 'Preguntas';

    protected static string|BackedEnum|null $icon = Heroicon::OutlinedQuestionMarkCircle;

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $cantidad = $ownerRecord->preguntas()->count();

        return $cantidad > 0 ? (string) $cantidad : null;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components(PreguntaForm::campos());
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('enunciado')
            ->modifyQueryUsing(fn ($query) => $query->withCount('opciones')->with('opciones'))
            ->defaultSort('orden')
            ->reorderable('orden')
            ->paginated([10, 25])
            ->emptyStateIcon(Heroicon::OutlinedQuestionMarkCircle)
            ->emptyStateHeading('Aún no hay preguntas')
            ->emptyStateDescription('Crea la primera con sus opciones y marca la correcta.')
            ->columns([
                TextColumn::make('enunciado')
                    ->label('Pregunta')
                    ->weight('semibold')
                    ->wrap()
                    ->limit(110),
                TextColumn::make('opciones_count')
                    ->label('Opciones')
                    ->badge()
                    ->color('secondary')
                    ->icon(Heroicon::OutlinedListBullet)
                    ->alignCenter(),
                TextColumn::make('correcta')
                    ->label('Correcta')
                    ->state(fn (Pregunta $record): ?string => $record->opciones->firstWhere('es_correcta', true)?->texto)
                    ->placeholder('Sin marcar')
                    ->color('success')
                    ->limit(40),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Nueva pregunta')
                    ->icon(Heroicon::OutlinedPlus)
                    ->modalHeading('Nueva pregunta')
                    ->modalDescription('Escribe la pregunta, sus opciones y marca la correcta.')
                    ->modalWidth(Width::ThreeExtraLarge)
                    ->mutateDataUsing(function (array $data): array {
                        $data['orden'] = ((int) $this->getOwnerRecord()->preguntas()->max('orden')) + 1;

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalHeading('Editar pregunta')
                    ->modalWidth(Width::ThreeExtraLarge),
                DeleteAction::make()
                    ->modalDescription('Se eliminarán también sus opciones.'),
            ]);
    }
}
