<?php

namespace App\Filament\Resources\Leccions;

use App\Filament\Resources\Leccions\Pages\CreateLeccion;
use App\Filament\Resources\Leccions\Pages\EditLeccion;
use App\Filament\Resources\Leccions\Pages\ListLeccions;
use App\Filament\Resources\Leccions\Schemas\LeccionForm;
use App\Filament\Resources\Leccions\Tables\LeccionsTable;
use App\Models\Leccion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class LeccionResource extends Resource
{
    protected static ?string $model = Leccion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Contenido';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Lecciones';

    protected static ?string $modelLabel = 'lección';

    protected static ?string $pluralModelLabel = 'lecciones';

    protected static ?string $recordTitleAttribute = 'titulo';

    public static function form(Schema $schema): Schema
    {
        return LeccionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LeccionsTable::configure($table);
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
            'index' => ListLeccions::route('/'),
            'create' => CreateLeccion::route('/create'),
            'edit' => EditLeccion::route('/{record}/edit'),
        ];
    }
}
