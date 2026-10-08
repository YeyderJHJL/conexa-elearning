<?php

namespace App\Filament\Resources\Modulos\Pages;

use App\Filament\Resources\Modulos\ModuloResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditModulo extends EditRecord
{
    protected static string $resource = ModuloResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
