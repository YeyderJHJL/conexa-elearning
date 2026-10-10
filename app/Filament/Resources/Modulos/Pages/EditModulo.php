<?php

namespace App\Filament\Resources\Modulos\Pages;

use App\Filament\Resources\Modulos\ModuloResource;
use App\Filament\Resources\Quizzes\QuizResource;
use App\Filament\Support\EditaConHijos;
use App\Filament\Support\FormularioCentrado;
use App\Models\Modulo;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditModulo extends EditRecord
{
    use EditaConHijos;
    use FormularioCentrado;

    protected static string $resource = ModuloResource::class;

    protected function etiquetaDeDatos(): string
    {
        return 'Datos del módulo';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('quiz')
                ->label(fn (): string => $this->moduloActual()->quiz()->exists() ? 'Editar quiz' : 'Crear quiz')
                ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                ->color('gray')
                ->action(function (): void {
                    $modulo = $this->moduloActual();

                    $quiz = $modulo->quiz()->firstOrCreate([], ['titulo' => 'Quiz: '.$modulo->titulo]);

                    $this->redirect(QuizResource::getUrl('edit', ['record' => $quiz]));
                }),
            DeleteAction::make(),
        ];
    }

    private function moduloActual(): Modulo
    {
        /** @var Modulo */
        return $this->getRecord();
    }
}
