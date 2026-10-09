<?php

namespace App\Filament\Resources\Areas\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AreaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('descripcion'),
                TextInput::make('icono'),
                FileUpload::make('resumen_pdf')
                    ->label('Resumen del área (PDF)')
                    ->acceptedFileTypes(['application/pdf'])
                    ->directory('resumenes'),
                TextInput::make('orden')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('activa')
                    ->required(),
            ]);
    }
}
