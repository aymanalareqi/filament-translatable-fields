# Filament Translatable Fields

[![Latest Version on Packagist](https://img.shields.io/packagist/v/alareqi/filament-translatable-fields.svg?style=flat-square)](https://packagist.org/packages/alareqi/filament-translatable-fields)
[![Total Downloads](https://img.shields.io/packagist/dt/alareqi/filament-translatable-fields.svg?style=flat-square)](https://packagist.org/packages/alareqi/filament-translatable-fields)

Make Filament form fields, infolist entries, and table columns translatable using [Spatie Laravel Translatable](https://github.com/spatie/laravel-translatable). Each component has its own language dropdown, styled with the panel's primary colors.

This README describes the current package source. See [Unreleased changes](CHANGELOG.md#unreleased) for features awaiting release.

## Requirements

- **Filament 4 or 5**
- **Laravel 10, 11, 12, or 13**
- **PHP 8.2+**, subject to the requirements of your chosen Laravel and Filament versions

## Installation

```bash
composer require alareqi/filament-translatable-fields
php artisan filament:assets --no-interaction
```

Register the plugin in your panel provider and configure its locales:

```php
use Alareqi\FilamentTranslatableFields\FilamentTranslatableFieldsPlugin;
use Filament\Panel;
use Filament\PanelProvider;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            // Your existing panel configuration...
            ->plugins([
                FilamentTranslatableFieldsPlugin::make()
                    ->supportedLocales([
                        'ar' => 'العربية',
                        'en' => 'English',
                    ])
                    ->localeFlags([
                        'ar' => '🇸🇦',
                        'en' => '🇬🇧',
                    ]),
            ]);
    }
}
```

`localeFlags()` is optional. Locales without a configured flag still show their code and label. `supportedLocales()` and `localeFlags()` also accept closures returning their respective arrays.

If no supported locales are configured, the package uses `config('app.locale')` as its single locale. An indexed list such as `['ar', 'en']` is also supported; labels are generated from the locale codes.

### Model setup

Use Spatie's `HasTranslations` trait and declare your translated attributes. Store these attributes in JSON-compatible columns.

```php
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Product extends Model
{
    use HasTranslations;

    public array $translatable = ['name', 'description'];
}
```

## Form fields

Call `translatable()` after configuring the underlying field:

```php
use Filament\Forms\Components\TextInput;

TextInput::make('name')
    ->label('Name')
    ->required()
    ->maxLength(255)
    ->translatable()
    ->columnSpanFull();
```

The field state contains one value per locale, such as `name.ar` and `name.en`. Translated fields also work inside repeaters.

### Language switcher

The dropdown shows the active locale code. Its options show locale codes, labels, optional flags, and a checkmark for the selected language. It uses the panel's primary colors and supports dark mode.

The switcher appears:

- Inside the suffix area of text inputs, alongside any existing suffix.
- Inside textareas that use the default field wrapper.
- In the rich editor toolbar. Disabled rich editors display it beside the label.
- Beside the label for other fields and textareas with a custom field wrapper.

Form input direction is set from the locale. Each translated field switches independently.

### Shared validation rules

Standard Filament validation methods apply to every locale:

```php
TextInput::make('title')
    ->required()
    ->minLength(3)
    ->maxLength(255)
    ->translatable();
```

This requires every translation, including languages that are not currently visible. Standard validation closures are preserved and evaluated by Filament on each localized field.

### Per-locale configuration

Pass a `configure` callback to apply different validation or field settings per locale:

```php
TextInput::make('name')
    ->maxLength(255)
    ->translatable(
        configure: fn (TextInput $field, string $locale): TextInput => $field
            ->required($locale === 'ar')
            ->minLength($locale === 'ar' ? 3 : 2),
    );
```

Arabic is required and must contain at least three characters. English is optional, but must contain at least two characters when supplied. Both retain the shared maximum length.

The callback receives the localized field clone first and its locale code second. It runs after shared settings are copied, so it can override settings such as `required()` or `maxLength()` for that locale. Configure the supplied field; the callback's return value is not used. A callback returning `void` is also supported:

```php
use Filament\Forms\Components\Textarea;

Textarea::make('description')
    ->maxLength(1000)
    ->translatable(
        configure: function (Textarea $field, string $locale): void {
            $field
                ->required($locale === 'ar')
                ->rows($locale === 'ar' ? 4 : 3);
        },
    );
```

Use the matching component type, such as `TextInput`, `Textarea`, or `RichEditor`, or use the base `Filament\Forms\Components\Field` type. The callback runs when the schema is built. Use Filament's normal method closures for conditions that need to be evaluated later.

`configure` is available on form fields. Infolists are read-only and do not use validation rules or this callback.

### Existing `localeValidation()` usage

`localeValidation()` remains supported for applications that supply Laravel rule arrays:

```php
TextInput::make('name')
    ->localeValidation(fn (string $locale): array => match ($locale) {
        'ar' => ['required', 'min:3', 'max:255'],
        default => ['nullable', 'min:2', 'max:255'],
    })
    ->translatable();
```

Call it before `translatable()`. Its callback receives the locale code and the original field clone, and returns an array of rules. These rules are added to the field's shared rules. When migrating a field to fluent validation, move its locale rules into `configure` and remove that field's `localeValidation()` call.

## Infolist entries

Use the same `translatable()` method on an entry:

```php
use Filament\Infolists\Components\TextEntry;

TextEntry::make('name')
    ->label('Name')
    ->translatable();

TextEntry::make('description')
    ->label('Description')
    ->html()
    ->translatable()
    ->columnSpanFull();
```

Entries use the same primary-colored dropdown as forms, placed beside the label. Existing labels, hints, and formatting are preserved. Each entry switches independently, and translations are read from the record using Spatie's `getTranslation()` method.

## Table columns

Add `InteractsWithTranslatableColumns` to the Livewire component that owns the table, such as a resource's list page:

```php
use Alareqi\FilamentTranslatableFields\Concerns\InteractsWithTranslatableColumns;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    use InteractsWithTranslatableColumns;

    protected static string $resource = ProductResource::class;
}
```

Then configure the columns in the resource's table:

```php
use Filament\Tables\Columns\TextColumn;

TextColumn::make('name')
    ->label('Name')
    ->searchable()
    ->sortable()
    ->translatable();

TextColumn::make('category.name')
    ->label('Category')
    ->searchable(isIndividual: true)
    ->sortable()
    ->translatable();
```

Call `translatable()` after `label()`, `searchable()`, `sortable()`, and other column configuration. Unlike forms and infolists, it returns the original column rather than a `Tabs` wrapper.

Each header gets the shared language dropdown. Selecting a language changes that column's displayed translations, global and individual search, and sorting. Other columns and the application locale keep their own language. The current search and sort stay active, and pagination returns to the first page. Choices belong to the current Livewire table instance and reset when the page is reloaded.

Use the trait on relation managers or standalone Livewire table components too. Without it, a translated column reads the active table locale or application locale and displays a plain heading, without a dropdown. If the initial locale is unsupported, the first configured locale is used.

Columns read translated attributes from Eloquent models, including relationships. Missing translations remain empty instead of falling back to another language, so the displayed language matches search and sorting. Use Filament's `placeholder()` or `default()` methods when needed. Existing formatting callbacks, cell attributes, and custom search or sort callbacks are retained. `translatable()` supplies the column's state callback; use `formatStateUsing()` to format the translated value.

Custom search and sort callbacks take precedence over the package's locale-aware defaults. If a custom callback needs the selected locale, read it from the table component:

```php
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

TextColumn::make('name')
    ->searchable(query: function (Builder $query, string $search, Component $livewire): Builder {
        $locale = $livewire->getTranslatableColumnLocale('name');

        return $query->where("name->{$locale}", 'like', "%{$search}%");
    })
    ->translatable();
```

## Custom locales

Override the panel's locales for an individual form field, infolist entry, or table column:

```php
TextInput::make('name')
    ->translatable(
        customLocales: ['ar' => 'العربية', 'en' => 'English', 'fr' => 'French'],
        configure: fn (TextInput $field, string $locale): TextInput => $field
            ->required($locale === 'ar'),
    );

TextEntry::make('name')
    ->translatable(
        customLocales: fn (): array => ['ar' => 'العربية', 'en' => 'English'],
    );

TextColumn::make('name')
    ->searchable()
    ->sortable()
    ->translatable(customLocales: ['ar' => 'العربية', 'en' => 'English']);
```

The existing positional syntax remains supported:

```php
TextInput::make('name')
    ->translatable(true, ['ar' => 'العربية', 'en' => 'English']);
```

Pass `false` as the first argument to leave a component untranslated:

```php
TextInput::make('name')->translatable(false);
TextEntry::make('name')->translatable(false);
TextColumn::make('name')->translatable(false);
```

Infolist entries also accept a closure returning the condition as their first argument.

## Method ordering

For forms and infolists, `translatable()` wraps the localized fields or entries in `Filament\Schemas\Components\Tabs`, with the tab bar hidden in favor of the language dropdown.

Methods before `translatable()` configure the field or entry and are copied to its locale clones. Methods after it configure the enclosing `Tabs` component. Put validation, formatting, and field-specific settings before `translatable()`, and layout settings such as `columnSpanFull()` after it.

For table columns, call `translatable()` last, after search and sort configuration, so those methods do not replace its query callbacks.

## Testing a local package checkout

In the consuming application's `composer.json`, add a path repository pointing to this package and merge the requirement into the existing `require` section:

```json
{
    "require": {
        "alareqi/filament-translatable-fields": "^2.0@dev"
    },
    "repositories": [
        {
            "type": "path",
            "url": "../packages/filament-translatable-fields",
            "options": {
                "symlink": true
            }
        }
    ]
}
```

The repository URL is relative to the application's `composer.json`. Adjust it for your directory structure. The example constraint allows the package's `2.x-dev` branch.

Then run these commands in the application:

```bash
composer update alareqi/filament-translatable-fields --no-interaction
php artisan filament:assets --no-interaction
```

PHP and Blade edits are read from the symlinked source. After changing the package's CSS, build it in the package directory:

```bash
npm install
npm run build:styles
```

Then run `php artisan filament:assets --no-interaction` in the application again and refresh the browser.

## Upgrading

See the [upgrade guide](UPGRADE-TO-V4.md) for migration from the Filament 3 version and notes about the current switcher and validation APIs.

## Changelog

See [CHANGELOG](CHANGELOG.md) for release history and unreleased changes.

## Credits

- [Ayman Alareqi](https://github.com/AymanAlareqi)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). See [License File](LICENSE.md) for more information.
