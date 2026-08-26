<?php

namespace App\Filament\Resources\JournalEntries;

use App\Filament\Resources\JournalEntries\Pages\CreateJournalEntry;
use App\Filament\Resources\JournalEntries\Pages\EditJournalEntry;
use App\Filament\Resources\JournalEntries\Pages\ListJournalEntries;
use App\Filament\Resources\JournalEntries\Pages\ViewJournalEntry;
use App\Filament\Resources\JournalEntries\Schemas\JournalEntryForm;
use App\Filament\Resources\JournalEntries\Tables\JournalEntriesTable;
use App\Models\JournalEntry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class JournalEntryResource extends Resource
{
    protected static ?string $model = JournalEntry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $recordTitleAttribute = 'document_number';

    protected static ?string $navigationLabel = 'القيود اليومية';

    protected static ?string $modelLabel = 'قيد يومية';

    protected static ?string $pluralModelLabel = 'القيود اليومية';

    protected static string|\UnitEnum|null $navigationGroup = 'الحسابات العامة';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return JournalEntryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JournalEntriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJournalEntries::route('/'),
            'create' => CreateJournalEntry::route('/create'),
            'view' => ViewJournalEntry::route('/{record}'),
            'edit' => EditJournalEntry::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::allowed('journal_entries.view');
    }

    public static function canAccess(): bool
    {
        return static::allowed('journal_entries.view');
    }

    public static function canViewAny(): bool
    {
        return static::allowed('journal_entries.view');
    }

    public static function canView(Model $record): bool
    {
        return static::allowed('journal_entries.view');
    }

    public static function canCreate(): bool
    {
        return static::allowed('journal_entries.create');
    }

    public static function canEdit(Model $record): bool
    {
        return $record instanceof JournalEntry
            && static::allowed('journal_entries.edit');
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof JournalEntry
            && $record->isManual()
            && static::allowed('journal_entries.delete');
    }

    private static function allowed(string $permission): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ((bool) ($user->is_admin ?? false)) {
            return true;
        }

        return method_exists($user, 'hasPermission')
            ? $user->hasPermission($permission)
            : true;
    }
}