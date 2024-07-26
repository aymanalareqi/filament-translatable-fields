<?php

namespace Alareqi\FilamentTranslatableFields;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Tabs;
use Filament\Panel;
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
                        return "<option value='-{$c_locale}-tab'" . ($c_locale == $locale ? ' selected' : '') . ">{$label2}</option>";
                    })->implode("");
                    $localeSelect .= "</select>";
                    // dd($localeSelect);
                    return Tabs\Tab::make($locale)
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
    }
}
