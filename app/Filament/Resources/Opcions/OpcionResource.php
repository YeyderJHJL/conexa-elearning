<?php

namespace App\Filament\Resources\Opcions;

use App\Filament\Resources\Opcions\Pages\CreateOpcion;
use App\Filament\Resources\Opcions\Pages\EditOpcion;
use App\Filament\Resources\Opcions\Pages\ListOpcions;
use App\Filament\Resources\Opcions\Schemas\OpcionForm;
use App\Filament\Resources\Opcions\Tables\OpcionsTable;
use App\Models\Opcion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class OpcionResource extends Resource
{
    protected static ?string $model = Opcion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'texto';

    public static function form(Schema $schema): Schema
    {
        return OpcionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OpcionsTable::configure($table);
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
            'index' => ListOpcions::route('/'),
            'create' => CreateOpcion::route('/create'),
            'edit' => EditOpcion::route('/{record}/edit'),
        ];
    }
}
