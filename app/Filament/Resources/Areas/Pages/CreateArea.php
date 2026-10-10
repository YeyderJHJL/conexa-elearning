<?php

namespace App\Filament\Resources\Areas\Pages;

use App\Filament\Resources\Areas\AreaResource;
use App\Filament\Support\CreaYAbreEdicion;
use App\Filament\Support\FormularioCentrado;
use Filament\Resources\Pages\CreateRecord;

class CreateArea extends CreateRecord
{
    use CreaYAbreEdicion;
    use FormularioCentrado;

    protected static string $resource = AreaResource::class;
}
