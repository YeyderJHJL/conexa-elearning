<?php

namespace App\Filament\Resources\Preguntas;

use App\Filament\Resources\Preguntas\Pages\CreatePregunta;
use App\Filament\Resources\Preguntas\Pages\EditPregunta;
use App\Filament\Resources\Preguntas\Pages\ListPreguntas;
use App\Filament\Resources\Preguntas\Schemas\PreguntaForm;
use App\Filament\Resources\Preguntas\Tables\PreguntasTable;
use App\Models\Pregunta;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PreguntaResource extends Resource
{
    protected static ?string $model = Pregunta::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Evaluación';

    /**
     * Se administra desde el formulario del módulo / del quiz; la tabla sigue disponible por URL.
     */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Preguntas';

    protected static ?string $modelLabel = 'pregunta';

    protected static ?string $pluralModelLabel = 'preguntas';

    protected static ?string $recordTitleAttribute = 'enunciado';

    public static function form(Schema $schema): Schema
    {
        return PreguntaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PreguntasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPreguntas::route('/'),
            'create' => CreatePregunta::route('/create'),
            'edit' => EditPregunta::route('/{record}/edit'),
        ];
    }
}
