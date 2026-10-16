<?php

namespace Alareqi\FilamentTranslatableFields\Concerns;

use Alareqi\FilamentTranslatableFields\Support\TranslatableColumn;
use Filament\Tables\Columns\Column;
use Livewire\Attributes\Locked;

trait InteractsWithTranslatableColumns
{
    /** @var array<string, string> */
    #[Locked]
    public array $translatableColumnLocales = [];

    public function getTranslatableColumnLocale(string $columnName): string
    {
        $column = $this->getTable()->getColumn($columnName);
        abort_unless($column instanceof Column && ($configuration = TranslatableColumn::from($column)), 422);

        return $configuration->resolveLocale($column, $this->translatableColumnLocales[$columnName] ?? null);
    }

    public function setTranslatableColumnLocale(string $columnName, string $locale): void
    {
        $column = $this->getTable()->getColumn($columnName);
        abort_unless($column instanceof Column && ($configuration = TranslatableColumn::from($column)), 422);
        abort_unless(array_key_exists($locale, $configuration->options($column)), 422);

        $this->translatableColumnLocales[$columnName] = $locale;
        $this->resetPage($this->getTablePaginationPageName());
        $this->flushCachedTableRecords();
    }
}
