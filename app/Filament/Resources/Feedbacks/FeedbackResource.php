<?php

namespace App\Filament\Resources\Feedbacks;

use App\Filament\Resources\Feedbacks\Pages\ListFeedbacks;
use App\Filament\Resources\Feedbacks\Tables\FeedbacksTable;
use App\Models\Feedback;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * Respuestas de la encuesta de fin de área. Es de solo lectura: el admin las consulta, no las edita.
 */
class FeedbackResource extends Resource
{
    protected static ?string $model = Feedback::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Reportes';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'feedback';

    protected static ?string $navigationLabel = 'Encuestas de feedback';

    protected static ?string $modelLabel = 'respuesta de encuesta';

    protected static ?string $pluralModelLabel = 'encuestas de feedback';

    public static function table(Table $table): Table
    {
        return FeedbacksTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFeedbacks::route('/'),
        ];
    }
}
