<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Datos personales')
                    ->description('Quién es y cuál es su puesto.')
                    ->icon(Heroicon::OutlinedUserCircle)
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('Correo electrónico')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        TextInput::make('cargo')
                            ->label('Cargo')
                            ->prefixIcon(Heroicon::OutlinedBriefcase)
                            ->maxLength(255),

                        DatePicker::make('fecha_ingreso')
                            ->label('Fecha de ingreso'),
                    ]),

                Section::make('Acceso a la plataforma')
                    ->description('Rol, contraseña y estado de la cuenta.')
                    ->icon(Heroicon::OutlinedLockClosed)
                    ->columns(2)
                    ->schema([
                        Select::make('rol')
                            ->label('Rol')
                            ->options(User::ETIQUETAS_ROL)
                            ->default('trabajador')
                            ->required(),

                        TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation) => $operation === 'create')
                            ->dehydrated(fn ($state) => filled($state))
                            ->dehydrateStateUsing(fn ($state) => bcrypt($state))
                            ->helperText(fn (string $operation): ?string => $operation === 'edit' ? 'Déjala vacía para conservar la actual.' : null),

                        Toggle::make('activo')
                            ->label('Cuenta activa')
                            ->default(true)
                            ->inline(false),

                        Toggle::make('debe_cambiar_password')
                            ->label('Debe cambiar la contraseña al ingresar')
                            ->default(true)
                            ->inline(false),
                    ]),

                Section::make('Áreas asignadas')
                    ->description('Las áreas de capacitación que verá este colaborador.')
                    ->icon(Heroicon::OutlinedAcademicCap)
                    ->schema([
                        Select::make('areas')
                            ->label('Áreas')
                            ->relationship('areas', 'nombre')
                            ->multiple()
                            ->preload(),
                    ]),
            ]);
    }
}
