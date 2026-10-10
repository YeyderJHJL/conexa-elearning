<?php

namespace App\Filament\Resources\Leccions\Pages;

use App\Filament\Resources\Leccions\LeccionResource;
use App\Filament\Support\GuardaVideoUnico;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLeccion extends EditRecord
{
    use GuardaVideoUnico;

    protected static string $resource = LeccionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
