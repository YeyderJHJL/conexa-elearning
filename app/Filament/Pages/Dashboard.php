<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $navigationLabel = 'Inicio';

    protected static ?string $title = 'Panel de control';

    public function getSubheading(): string|Htmlable|null
    {
        return 'Avance real de la capacitación de tu equipo en Conexa Capital Central.';
    }

    public function getColumns(): int|array
    {
        return ['md' => 2, 'xl' => 2];
    }
}
