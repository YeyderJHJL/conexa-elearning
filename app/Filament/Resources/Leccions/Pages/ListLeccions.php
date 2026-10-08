<?php

namespace App\Filament\Resources\Leccions\Pages;

use App\Filament\Resources\Leccions\LeccionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLeccions extends ListRecords
{
    protected static string $resource = LeccionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
