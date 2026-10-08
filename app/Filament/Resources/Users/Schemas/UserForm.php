<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required(),

                TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),

                Select::make('rol')
                    ->options(['admin' => 'Administrador', 'trabajador' => 'Trabajador'])
                    ->default('trabajador')
                    ->required(),

                TextInput::make('cargo'),

                DatePicker::make('fecha_ingreso'),

                Select::make('areas')
                    ->relationship('areas', 'nombre')
                    ->multiple()
                    ->preload()
                    ->label('Áreas asignadas'),

                TextInput::make('password')
                    ->password()
                    ->required(fn (string $operation) => $operation === 'create')
                    ->dehydrated(fn ($state) => filled($state))
                    ->dehydrateStateUsing(fn ($state) => bcrypt($state)),

                Toggle::make('activo')
                    ->default(true),

                Toggle::make('debe_cambiar_password')
                    ->label('Debe cambiar contraseña al ingresar')
                    ->default(true),
            ]);
    }
}
