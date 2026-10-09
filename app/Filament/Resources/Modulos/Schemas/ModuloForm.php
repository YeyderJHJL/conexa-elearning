<?php

namespace App\Filament\Resources\Modulos\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ModuloForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('area_id')
                    ->relationship('area', 'nombre')
                    ->required(),
                TextInput::make('titulo')
                    ->required(),
                TextInput::make('descripcion'),
                FileUpload::make('resumen_pdf')
                    ->label('Resumen del módulo (PDF)')
                    ->acceptedFileTypes(['application/pdf'])
                    ->directory('resumenes'),
                TextInput::make('orden')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('activo')
                    ->required(),
            ]);
    }
}
