<?php

namespace Alareqi\FilamentTranslatableFields;

use Alareqi\FilamentTranslatableFields\Support\LocaleSwitcher;
use Closure;
use Filament\Contracts\Plugin;
use Filament\Forms\Components\Field;
use Filament\Infolists\Components\Entry;
use Filament\Panel;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Tables\Columns\Column;
use Illuminate\Support\HtmlString;

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
        $supportedLocales = $this->getSupportedLocales();

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
        Entry::macro('translatable', function ($condition = true, array|Closure|null $customLocales = null) use ($supportedLocales) {
            $entry = $this->getClone();
            $locales = $customLocales ?? $supportedLocales;
            $tabs = collect($locales)
                ->map(function ($label, $key) use ($entry, $locales) {
                    $locale = is_string($key) ? $key : $label;
                    $localeLabel = ! is_int($key) ? $label : locale_get_display_name($locale, app()->getLocale());
                    $localeSelect = "<select class='translatable-field-locale-select fi-select-input fi-input-wrp' x-model='tab'>";
                    $localeSelect .= collect($locales)->map(function ($label2, $key2) use ($locale) {
                        $c_locale = is_string($key2) ? $key2 : $label2;
                        $llabel = ! is_int($key2) ? $label2 : locale_get_display_name($c_locale, app()->getLocale());

                        return "<option value='-{$c_locale}-tab'".($c_locale == $locale ? ' selected' : '').">{$llabel}</option>";
                    })->implode('');
                    $localeSelect .= '</select>';
                    $newEntry = $entry->getClone();
                    $newEntry
                        ->name("{$entry->getName()}.{$locale}")
                        ->label($entry->getLabel()." [{$localeLabel}]")
                        ->statePath("{$entry->getStatePath(false)}.{$locale}")
                        ->getStateUsing(function ($record) use ($entry, $locale) {
                            return $record->getTranslation($entry->getName(), $locale);
                        })
                        ->hintIcon('heroicon-o-language', 'Translatable Field')
                        ->hint(new HtmlString($localeSelect));

                    return Tab::make($locale)
                        ->label(is_string($key) ? $label : strtoupper($locale))
                        ->key("-{$locale}-tab")
                        ->schema([
                            $newEntry,
                        ]);
                })
                ->toArray();

            return Tabs::make('translations')
                ->tabs($tabs)
                ->contained(false)
                ->extraAttributes(['class' => 'translatable-field-tabs']);
        });
        Column::macro('translatable', function () use ($supportedLocales) {
            $label = $this->getLabel();
            $locales = $customLocales ?? $supportedLocales;
            $this->label(function ($livewire) use ($label, $locales) {

                $activeLocale = $livewire->getActiveTableLocale();

                $localeLabel = is_string(array_keys($locales)[0]) ? $locales[$activeLocale] : locale_get_display_name($activeLocale, app()->getLocale());

                return $label." [$localeLabel]";
            });

            return $this;
        });
    }
}
