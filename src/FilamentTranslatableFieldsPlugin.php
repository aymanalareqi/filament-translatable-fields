<?php

namespace Alareqi\FilamentTranslatableFields;

use Alareqi\FilamentTranslatableFields\Support\LocaleSwitcher;
use Alareqi\FilamentTranslatableFields\Support\TranslatableColumn;
use Closure;
use Filament\Contracts\Plugin;
use Filament\Forms\Components\Field;
use Filament\Infolists\Components\Entry;
use Filament\Panel;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Tables\Columns\Column;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FilamentTranslatableFieldsPlugin implements Plugin
{
    protected array|Closure $supportedLocales = [];

    protected array|Closure $localeFlags = [];

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        return filament(app(static::class)->getId());
    }

    public function getId(): string
    {
        return 'alareqi-filament-translatable-fields';
    }

    public function supportedLocales(array|Closure $supportedLocales): static
    {
        $this->supportedLocales = $supportedLocales;

        return $this;
    }

    public function getSupportedLocales(): array
    {
        $locales = is_callable($this->supportedLocales) ? call_user_func($this->supportedLocales) : $this->supportedLocales;

        if (empty($locales)) {
            $locales[] = config('app.locale');
        }

        return $locales;
    }

    /** @param array<string, string>|Closure $flags */
    public function localeFlags(array|Closure $flags): static
    {
        $this->localeFlags = $flags;

        return $this;
    }

    /** @return array<string, string> */
    public function getLocaleFlags(): array
    {
        return $this->localeFlags instanceof Closure ? ($this->localeFlags)() : $this->localeFlags;
    }

    public function register(Panel $panel): void
    {
        //
    }

    public function boot(Panel $panel): void
    {
        // Add macro for locale-specific validation rules
        Field::macro('localeValidation', function (Closure $validationRules) {
            /** @var Field $this */
            // Store the locale validation closure directly on the field object
            $this->localeValidationRules = $validationRules;

            return $this;
        });

        $plugin = $this;
        Field::macro('translatable', function (bool $translatable = true, array|Closure|null $customLocales = null, ?Closure $configure = null) use ($plugin): Field|Tabs {
            /** @var Field $this */
            if (! $translatable) {
                return $this;
            }

            $field = $this->getClone();
            $locales = $customLocales instanceof Closure ? $this->evaluate($customLocales) : ($customLocales ?? $plugin->getSupportedLocales());
            $options = [];
            foreach ($locales as $key => $label) {
                $locale = is_string($key) ? $key : $label;
                $options[$locale] = [
                    'label' => is_string($key) ? $label : locale_get_display_name($locale, app()->getLocale()),
                    'tab' => $field->getName().'-'.$locale.'-tab',
                    'flag' => $plugin->getLocaleFlags()[$locale] ?? null,
                ];
            }

            $localeValidationRules = property_exists($field, 'localeValidationRules') ? $field->localeValidationRules : null;
            $tabs = [];
            foreach ($options as $locale => $option) {
                $localeField = $field->getClone()
                    ->name($field->getName().'.'.$locale)
                    ->label($field->getLabel())
                    ->statePath($field->getStatePath(false).'.'.$locale);

                if ($localeValidationRules !== null) {
                    $rules = $localeValidationRules($locale, $field);
                    if (! empty($rules)) {
                        if (in_array('required', $rules, true)) {
                            $localeField->required();
                        }
                        $localeField->rules($rules);
                    }
                }

                if ($configure !== null) {
                    $configure($localeField, $locale);
                }

                LocaleSwitcher::configure($localeField, $locale, $options);
                $tabs[] = Tab::make($locale)->label($option['label'])->key($option['tab'])->schema([$localeField]);
            }

            return Tabs::make($field->getName().'Translations')
                ->tabs($tabs)->contained(false)
                ->extraAttributes(['class' => 'translatable-field-tabs']);
        });
        Entry::macro('translatable', function (bool|Closure $condition = true, array|Closure|null $customLocales = null) use ($plugin): Entry|Tabs {
            /** @var Entry $this */
            if (! $this->evaluate($condition)) {
                return $this;
            }

            $entry = $this->getClone();
            $locales = $customLocales instanceof Closure ? $this->evaluate($customLocales) : ($customLocales ?? $plugin->getSupportedLocales());
            $options = [];
            foreach ($locales as $key => $label) {
                $locale = is_string($key) ? $key : $label;
                $options[$locale] = [
                    'label' => is_string($key) ? $label : locale_get_display_name($locale, app()->getLocale()),
                    'tab' => $entry->getName().'-'.$locale.'-tab',
                    'flag' => $plugin->getLocaleFlags()[$locale] ?? null,
                ];
            }

            $tabs = [];
            foreach ($options as $locale => $option) {
                $localeEntry = $entry->getClone()
                    ->name($entry->getName().'.'.$locale)
                    ->label($entry->getLabel())
                    ->statePath($entry->getStatePath(false).'.'.$locale)
                    ->getStateUsing(function (Model $record) use ($entry, $locale): mixed {
                        return $record->getTranslation($entry->getName(), $locale);
                    });

                LocaleSwitcher::configure($localeEntry, $locale, $options);
                $tabs[] = Tab::make($locale)->label($option['label'])->key($option['tab'])->schema([$localeEntry]);
            }

            return Tabs::make($entry->getName().'Translations')
                ->tabs($tabs)
                ->contained(false)
                ->extraAttributes(['class' => 'translatable-field-tabs']);
        });
        Column::macro('translatable', function (bool $translatable = true, array|Closure|null $customLocales = null) use ($plugin): Column {
            /** @var Column $this */
            if (! $translatable) {
                return $this;
            }

            $configuration = new TranslatableColumn(clone $this, $plugin, $customLocales);
            $this->viewData(['translatableColumn' => $configuration])
                ->label(fn (Column $column) => $configuration->label($column))
                ->getStateUsing(fn (Column $column, Model $record): mixed => $configuration->state($column, $record))
                ->extraAttributes(fn (Column $column): array => [
                    'lang' => $configuration->locale($column),
                    'dir' => LocaleSwitcher::direction($configuration->locale($column)),
                ], merge: true);

            $this->searchQuery ??= fn (Column $column, Builder $query, string $search): Builder => $configuration->search($column, $query, $search);
            $this->sortQuery ??= fn (Column $column, Builder $query, string $direction): Builder => $configuration->sort($column, $query, $direction);

            return $this;
        });
    }
}
