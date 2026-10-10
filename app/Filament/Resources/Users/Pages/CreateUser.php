<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Filament\Support\FormularioCentrado;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    use FormularioCentrado;

    protected static string $resource = UserResource::class;
}
