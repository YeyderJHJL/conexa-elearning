<?php

namespace App\Filament\Resources\Quizzes\Pages;

use App\Filament\Resources\Quizzes\QuizResource;
use App\Filament\Support\CreaYAbreEdicion;
use App\Filament\Support\FormularioCentrado;
use Filament\Resources\Pages\CreateRecord;

class CreateQuiz extends CreateRecord
{
    use CreaYAbreEdicion;
    use FormularioCentrado;

    protected static string $resource = QuizResource::class;
}
