<?php

namespace App\Filament\Resources\Quizzes\Pages;

use App\Filament\Resources\Quizzes\QuizResource;
use App\Filament\Support\EditaConHijos;
use App\Filament\Support\FormularioCentrado;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditQuiz extends EditRecord
{
    use EditaConHijos;
    use FormularioCentrado;

    protected static string $resource = QuizResource::class;

    protected function etiquetaDeDatos(): string
    {
        return 'Datos del quiz';
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
