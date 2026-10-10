<?php

namespace App\Filament\Resources\Opcions\Pages;

use App\Filament\Resources\Opcions\OpcionResource;
use App\Filament\Support\FormularioCentrado;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOpcion extends EditRecord
{
    use FormularioCentrado;

    protected static string $resource = OpcionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
