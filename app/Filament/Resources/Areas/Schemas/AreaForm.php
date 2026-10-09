<?php

namespace App\Filament\Resources\Areas\Schemas;

use App\Models\Area;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
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
                TextInput::make('icono')
                    ->helperText('Clase de Bootstrap Icons, por ejemplo bi-cart. Se muestra cuando no hay imagen.'),
                ColorPicker::make('color')
                    ->label('Color de acento')
                    ->hex()
                    ->helperText('Opcional. Si lo dejas vacío se usa el azul de marca.')
                    ->hintActions(collect([
                        'Azul de marca' => Area::COLOR_MARCA,
                        'Dorado' => Area::COLOR_DORADO,
                        'Azul complementario' => Area::COLOR_COMPLEMENTARIO,
                    ])->map(fn (string $hex, string $nombre) => Action::make('color_'.str($nombre)->slug('_'))
                        ->label($nombre)
                        ->link()
                        ->action(fn (Set $set) => $set('color', $hex))
                    )->values()->all()),
                FileUpload::make('imagen')
                    ->label('Imagen de portada')
                    ->image()
                    ->disk('public')
                    ->visibility('public')
                    ->directory('areas')
                    ->helperText('Opcional. Se muestra como portada de la tarjeta del área.'),
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
