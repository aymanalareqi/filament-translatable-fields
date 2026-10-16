<?php

namespace Alareqi\FilamentTranslatableFields\Support;

use Alareqi\FilamentTranslatableFields\FilamentTranslatableFieldsPlugin;
use Closure;
use Filament\Tables\Columns\Column;
use Filament\Tables\View\TablesRenderHook;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class TranslatableColumn
{
    public function __construct(
        protected Column $original,
        protected FilamentTranslatableFieldsPlugin $plugin,
        protected array|Closure|null $customLocales = null,
    ) {}

    public static function from(Column $column): ?self
    {
        $configuration = $column->getViewData()['translatableColumn'] ?? null;

        return $configuration instanceof self ? $configuration : null;
    }

    public static function hasHeaderRenderHook(): bool
    {
        return defined(TablesRenderHook::class.'::HEADER_CELL');
    }

    /** @return array<string, array{label: string, flag: ?string}> */
    public function options(Column $column): array
    {
        $locales = $this->customLocales instanceof Closure
            ? $column->evaluate($this->customLocales)
            : ($this->customLocales ?? $this->plugin->getSupportedLocales());
        $options = [];

        foreach ($locales as $key => $label) {
            $locale = is_string($key) ? $key : $label;
            if (! is_string($locale) || ! preg_match('/^[a-zA-Z][a-zA-Z0-9_-]*$/D', $locale)) {
                throw new InvalidArgumentException('Translatable columns require valid locale codes.');
            }

            $options[$locale] = [
                'label' => is_string($key) ? $label : locale_get_display_name($locale, app()->getLocale()),
                'flag' => $this->plugin->getLocaleFlags()[$locale] ?? null,
            ];
        }

        if ($options === []) {
            throw new InvalidArgumentException('A translatable column requires at least one locale.');
        }

        return $options;
    }

    public function resolveLocale(Column $column, ?string $selected = null): string
    {
        $options = $this->options($column);
        foreach ([$selected, $column->getLivewire()->getActiveTableLocale(), app()->getLocale()] as $locale) {
            if ($locale !== null && array_key_exists($locale, $options)) {
                return $locale;
            }
        }

        return array_key_first($options);
    }

    public function locale(Column $column): string
    {
        $livewire = $column->getLivewire();

        return method_exists($livewire, 'getTranslatableColumnLocale')
            ? $livewire->getTranslatableColumnLocale($column->getName())
            : $this->resolveLocale($column);
    }

    public function label(Column $column): string|Htmlable
    {
        $label = (clone $this->original)->table($column->getTable())->getLabel();
        if (self::hasHeaderRenderHook() || ! method_exists($column->getLivewire(), 'setTranslatableColumnLocale')) {
            return $label;
        }

        return new ColumnHeaderLabel($label, view('alareqi-filament-translatable-fields::column-header', [
            'label' => $label,
            'columnName' => $column->getName(),
            'locale' => $this->locale($column),
            'options' => $this->options($column),
        ]));
    }

    public function state(Column $column, Model $record): mixed
    {
        $locale = $this->locale($column);
        $attribute = $column->getAttributeName($record);
        $translate = fn (Model $model): mixed => $this->isTranslated($model, $attribute)
            ? $model->getTranslation($attribute, $locale, useFallbackLocale: false)
            : data_get($model, $attribute);

        if (! $column->hasRelationship($record)) {
            return $translate($record);
        }

        $values = array_map($translate, $column->getRelationshipResults($record));

        return match (count($values)) {
            0 => null,
            1 => $values[0],
            default => $values,
        };
    }

    public function search(Column $column, Builder $query, string $search): Builder
    {
        $original = (clone $this->original)->table($column->getTable());
        $paths = $this->paths($column, $query->getModel(), $original->getSearchColumns($query->getModel()));
        $original->searchable($paths);
        $isFirst = true;

        return $original->applySearchConstraint($query, $search, $isFirst);
    }

    public function sort(Column $column, Builder $query, string $direction): Builder
    {
        $original = (clone $this->original)->table($column->getTable());
        $paths = $this->paths($column, $query->getModel(), $original->getSortColumns($query->getModel()));

        return $original->sortable($paths)->applySort($query, $direction);
    }

    /** @param list<string> $attributes
     * @return list<string>
     */
    protected function paths(Column $column, Model $model, array $attributes): array
    {
        $related = $column->hasRelationship($model) ? $column->getRelationship($model)->getRelated() : $model;
        $locale = $this->locale($column);

        return array_map(function (string $attribute) use ($related, $locale): string {
            $name = str_starts_with($attribute, $related->getTable().'.')
                ? substr($attribute, strlen($related->getTable()) + 1)
                : $attribute;

            return $this->isTranslated($related, $name) ? $attribute.'->'.$locale : $attribute;
        }, $attributes);
    }

    protected function isTranslated(Model $model, string $attribute): bool
    {
        return method_exists($model, 'isTranslatableAttribute') && $model->isTranslatableAttribute($attribute);
    }
}
