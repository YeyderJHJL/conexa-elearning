<?php

namespace App\Filament\Resources\Opcions\Pages;

use App\Filament\Resources\Opcions\OpcionResource;
use App\Filament\Support\FormularioCentrado;
use Filament\Resources\Pages\CreateRecord;

class CreateOpcion extends CreateRecord
{
    use FormularioCentrado;

    protected static string $resource = OpcionResource::class;
}
