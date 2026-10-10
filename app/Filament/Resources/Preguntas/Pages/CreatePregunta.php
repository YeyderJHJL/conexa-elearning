<?php

namespace App\Filament\Resources\Preguntas\Pages;

use App\Filament\Resources\Preguntas\PreguntaResource;
use App\Filament\Support\FormularioCentrado;
use Filament\Resources\Pages\CreateRecord;

class CreatePregunta extends CreateRecord
{
    use FormularioCentrado;

    protected static string $resource = PreguntaResource::class;
}
