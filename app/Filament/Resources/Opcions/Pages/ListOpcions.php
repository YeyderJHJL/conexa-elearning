<?php

namespace App\Filament\Resources\Opcions\Pages;

use App\Filament\Resources\Opcions\OpcionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOpcions extends ListRecords
{
    protected static string $resource = OpcionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
