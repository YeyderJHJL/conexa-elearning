<?php

namespace App\Filament\Resources\Modulos\Pages;

use App\Filament\Resources\Modulos\ModuloResource;
use App\Filament\Support\CreaYAbreEdicion;
use App\Filament\Support\FormularioCentrado;
use Filament\Resources\Pages\CreateRecord;

class CreateModulo extends CreateRecord
{
    use CreaYAbreEdicion;
    use FormularioCentrado;

    protected static string $resource = ModuloResource::class;
}
