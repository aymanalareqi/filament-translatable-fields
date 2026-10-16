# Upgrading from the Filament 3 version

The package's 2.x line uses Filament's unified schema components. The current source supports Filament 4 and 5. See the [changelog](CHANGELOG.md) for the release status of individual features.

## Requirements

- **Filament 4 or 5**
- **Laravel 10, 11, 12, or 13**
- **PHP 8.2+**, subject to the requirements of your chosen Laravel and Filament versions

Upgrade your application to a supported Filament version before installing this package version. Allowing beta packages globally is not required when using stable releases.

## Update the package

Update the application's package requirement to the 2.x line, then resolve its dependencies:

```bash
composer require "alareqi/filament-translatable-fields:^2.0" --with-all-dependencies
php artisan filament:assets --no-interaction
```

For an unreleased local checkout, follow the [path repository instructions](README.md#testing-a-local-package-checkout).

## Existing usage

Basic field and entry declarations remain supported:

```php
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;

TextInput::make('title')
    ->label('Title')
    ->translatable();

TextEntry::make('title')
    ->label('Title')
    ->translatable();

TextInput::make('description')
    ->translatable(true, [
        'ar' => 'العربية',
        'en' => 'English',
    ]);
```

The wrapper uses `Filament\Schemas\Components\Tabs`, and its tabs use `Filament\Schemas\Components\Tabs\Tab`. Update any application customizations that reference older form-specific tab classes.

## Language switcher

The current source replaces the native language select with a dropdown using the panel's primary colors. Text inputs, textareas, and rich editors show the switcher inside the control or toolbar. Other form fields and infolist entries show it beside the label.

Labels no longer need a locale suffix because the switcher displays the active locale. Existing suffix text, hints, and entry formatting are retained. Each field or entry has its own language selection.

Optional flags are configured on the panel plugin:

```php
FilamentTranslatableFieldsPlugin::make()
    ->supportedLocales(['ar' => 'العربية', 'en' => 'English'])
    ->localeFlags(['ar' => '🇸🇦', 'en' => '🇬🇧']);
```

## Validation

Shared fluent rules, including `required()`, are now retained on every locale clone:

```php
TextInput::make('title')
    ->required()
    ->maxLength(255)
    ->translatable();
```

Review fields that already call `required()` before `translatable()`: every configured locale will now be required. To require only one language, configure each locale explicitly:

```php
TextInput::make('title')
    ->maxLength(255)
    ->translatable(
        configure: fn (TextInput $field, string $locale): TextInput => $field
            ->required($locale === 'ar'),
    );
```

`localeValidation()` is still supported. To migrate a field, move its rules into shared fluent methods and the per-locale `configure` callback, then remove its `localeValidation()` call. The callback receives the localized field and locale code; it modifies that field without replacing it.

Call field-specific methods before `translatable()`. Methods chained afterward configure the enclosing `Tabs` component. Infolists are read-only and do not accept validation rules or the form-only `configure` callback.

## Verification

After upgrading, check these behaviors in the consuming application:

1. Open and switch languages on text inputs, textareas, and rich editors.
2. Save both translations and confirm they are retained when reopening the record.
3. Submit missing or invalid values in an inactive locale and confirm validation still runs.
4. Check optional locales, per-locale length limits, and any translated repeater fields.
5. View infolist entries and switch each entry independently.
6. Check any custom wrappers, suffixes, hints, and formatting used by your application.

Run the package tests from the package checkout:

```bash
vendor/bin/phpunit --no-coverage
```

Run the consuming application's affected tests as well.

## Troubleshooting

If the dropdown styles are missing or stale, run `php artisan filament:assets --no-interaction` in the application and refresh the browser. When developing the package's styles locally, run `npm run build:styles` in the package before publishing assets.

If Composer reports a conflict, check the installed PHP, Laravel, and Filament versions against the package's `composer.json`. For local path repositories, also check that the version constraint matches the checkout's branch.

Report package issues through [GitHub Issues](https://github.com/aymanalareqi/filament-translatable-fields/issues).
