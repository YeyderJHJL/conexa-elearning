<?php

namespace App\Filament\Resources\Preguntas\Pages;

use App\Filament\Resources\Preguntas\PreguntaResource;
use App\Filament\Support\FormularioCentrado;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPregunta extends EditRecord
{
    use FormularioCentrado;

    protected static string $resource = PreguntaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
