<?php

namespace App\Filament\Resources\Modulos;

use App\Filament\Resources\Modulos\Pages\CreateModulo;
use App\Filament\Resources\Modulos\Pages\EditModulo;
use App\Filament\Resources\Modulos\Pages\ListModulos;
use App\Filament\Resources\Modulos\RelationManagers\LeccionesRelationManager;
use App\Filament\Resources\Modulos\Schemas\ModuloForm;
use App\Filament\Resources\Modulos\Tables\ModulosTable;
use App\Models\Modulo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ModuloResource extends Resource
{
    protected static ?string $model = Modulo::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Contenido';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Módulos';

    protected static ?string $modelLabel = 'módulo';

    protected static ?string $pluralModelLabel = 'módulos';

    protected static ?string $recordTitleAttribute = 'titulo';

    public static function form(Schema $schema): Schema
    {
        return ModuloForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ModulosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            LeccionesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListModulos::route('/'),
            'create' => CreateModulo::route('/create'),
            'edit' => EditModulo::route('/{record}/edit'),
        ];
    }
}
