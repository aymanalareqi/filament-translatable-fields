<?php

namespace Alareqi\FilamentTranslatableFields;

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

    public function register(Panel $panel): void
    {
        //
    }

    public function boot(Panel $panel): void
    {
        $supportedLocales = $this->getSupportedLocales();

        Field::macro('translatable', function (bool $translatable = true, array | Closure | null $customLocales = null) use ($supportedLocales) {
            if (!$translatable) {
                return $this;
            }

            /**
             * @var Field $field
             * @var Field $this
             */
            $field = $this->getClone();

            $tabs = collect($customLocales  ?? $supportedLocales)
                ->map(function ($label, $key) use ($field, $customLocales, $supportedLocales) {
                    $locale = is_string($key) ? $key : $label;
                    $localeSelect = "<select class='translatable-field-locale-select' x-model='tab'>";
                    $localeSelect .= collect($customLocales  ?? $supportedLocales)->map(function ($label2, $key2) use ($locale) {
                        $c_locale = is_string($key2) ? $key2 : $label2;
                        $llabel = !is_integer($key2) ? $label2 : locale_get_display_name($c_locale, app()->getLocale());
                        return "<option value='-{$c_locale}-tab'" . ($c_locale == $locale ? ' selected' : '') . ">{$llabel}</option>";
                    })->implode("");
                    $localeSelect .= "</select>";
                    // dd($localeSelect);
                    return Tab::make($locale)
                        ->label(is_string($key) ? $label : strtoupper($locale))
                        ->schema([
                            $field
                                ->getClone()
                                ->name("{$field->getName()}.{$locale}")
                                ->label($field->getLabel() . " ({$locale})")
                                ->statePath("{$field->getStatePath(false)}.{$locale}")
                                ->hintIcon('heroicon-o-language', 'Translatable Field')
                                ->hint(new HtmlString($localeSelect)),
                        ]);
                })
                ->toArray();

            $tabsField = Tabs::make('translations')
                ->tabs($tabs)
                ->contained(false)
                ->extraAttributes(['class' => 'translatable-field-tabs']);

            return $tabsField;
        });
        Entry::macro('translatable', function ($condition = true, array | Closure | null $customLocales = null) use ($supportedLocales) {
            $entry = $this->getClone();

            $tabs = collect($customLocales  ?? $supportedLocales)
                ->map(function ($label, $key) use ($entry, $customLocales, $supportedLocales) {
                    $locale = is_string($key) ? $key : $label;
                    $localeSelect = "<select class='translatable-field-locale-select' x-model='tab'>";
                    $localeSelect .= collect($customLocales  ?? $supportedLocales)->map(function ($label2, $key2) use ($locale) {
                        $c_locale = is_string($key2) ? $key2 : $label2;
                        $llabel = !is_integer($key2) ? $label2 : locale_get_display_name($c_locale, app()->getLocale());
                        return "<option value='-{$c_locale}-tab'" . ($c_locale == $locale ? ' selected' : '') . ">{$llabel}</option>";
                    })->implode("");
                    $localeSelect .= "</select>";
                    $newEntry =  $entry->getClone();
                    $newEntry
                        ->name("{$entry->getName()}.{$locale}")
                        ->label($entry->getLabel() . " ({$locale})")
                        ->statePath("{$entry->getStatePath(false)}.{$locale}")
                        ->getStateUsing(function ($record) use ($entry, $locale) {
                            return $record->getTranslation($entry->getName(), $locale);
                        })
                        ->hintIcon('heroicon-o-language', 'Translatable Field')
                        ->hint(new HtmlString($localeSelect));
                    return Tab::make($locale)
                        ->label(is_string($key) ? $label : strtoupper($locale))
                        ->schema([
                            $newEntry
                        ]);
                })
                ->toArray();
            return Tabs::make('translations')
                ->tabs($tabs)
                ->contained(false)
                ->extraAttributes(['class' => 'translatable-field-tabs']);
        });
        Column::macro('translatable', function () {
            $label = $this->getLabel();
            $this->label(function ($livewire) use ($label) {

                $activeLocale = $livewire->getActiveTableLocale();

                return $label . " ($activeLocale)";
            });
            return $this;
        });
    }
}
