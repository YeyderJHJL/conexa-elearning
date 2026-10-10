<?php

namespace App\Filament\Resources\Leccions\Pages;

use App\Filament\Resources\Leccions\LeccionResource;
use App\Filament\Support\FormularioCentrado;
use App\Filament\Support\GuardaVideoUnico;
use Filament\Resources\Pages\CreateRecord;

class CreateLeccion extends CreateRecord
{
    use FormularioCentrado;
    use GuardaVideoUnico;

    protected static string $resource = LeccionResource::class;
}
