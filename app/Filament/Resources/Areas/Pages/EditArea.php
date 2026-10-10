<?php

namespace App\Filament\Resources\Areas\Pages;

use App\Filament\Resources\Areas\AreaResource;
use App\Filament\Support\EditaConHijos;
use App\Filament\Support\FormularioCentrado;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditArea extends EditRecord
{
    use EditaConHijos;
    use FormularioCentrado;

    protected static string $resource = AreaResource::class;

    protected function etiquetaDeDatos(): string
    {
        return 'Datos del área';
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
