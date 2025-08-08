# Filament Translatable Fields

[![Latest Version on Packagist](https://img.shields.io/packagist/v/alareqi/filament-translatable-fields.svg?style=flat-square)](https://packagist.org/packages/alareqi/filament-translatable-fields)
[![Total Downloads](https://img.shields.io/packagist/dt/alareqi/filament-translatable-fields.svg?style=flat-square)](https://packagist.org/packages/alareqi/filament-translatable-fields)

This package adds a way to make all filament fields translatable.
It uses the `spatie/laravel-translatable` package in the background.

**This version is specifically designed for Filament v4.**

## Requirements

- **Filament v4.0+** (currently in beta)
- **Laravel 10, 11, or 12**
- **PHP 8.2+**

## Installation

Since Filament v4 is currently in beta, you need to configure Composer to allow beta packages:

```bash
composer config minimum-stability beta
```

Then install the package:

```bash
composer require alareqi/filament-translatable-fields
```

Add the plugin to your desired Filament panel:

```php
use Alareqi\FilamentTranslatableFields\FilamentTranslatableFieldsPlugin;

class FilamentPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            // ...
            ->plugins([
                FilamentTranslatableFieldsPlugin::make(),
            ]);
    }
}
```

You can specify the supported locales:

```php
use Alareqi\FilamentTranslatableFields\FilamentTranslatableFieldsPlugin;

class FilamentPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            // ...
            ->plugins([
                FilamentTranslatableFieldsPlugin::make()
                    ->supportedLocales([
                        'en' => 'English',
                        'ar' => 'العربية',
                    ]),
            ]);
    }
}
```

By default, the package will use the `app.locale` if you don't specify the locales.

## Usage

You can simply add `->translatable()` to any field to make it translatable.

```php
use Filament\Forms\Components\TextInput;

TextInput::make('name')
    ->label('Name')
    ->translatable(),
```

## Overwrite locales

If you want to overwrite the locales on a specific field you can set the locales through the second parameter of the `->translatable()` function.

```php
use Filament\Forms\Components\TextInput;

TextInput::make('name')
    ->label('Name')
    ->translatable(true, ['en' => 'English', 'ar' => 'العربية', 'fr' => 'French']),
```

### Good to know

This package will substitute the original field with a `Filament\Schemas\Components\Tabs` component. This component will render the original field for each locale.

All chained methods you add before calling `->translatable()` will be applied to the original field.
All chained methods you add after calling `->translatable()` will be applied to the `Filament\Schemas\Components\Tabs` component.

## Filament v4 Changes

This version has been updated for Filament v4 with the following changes:

- **Unified Schema Components**: All Tab components now use the unified `Filament\Schemas\Components\Tabs\Tab` namespace
- **Updated Dependencies**: Requires Filament v4.0+ and PHP 8.2+
- **Beta Stability**: Requires `minimum-stability: beta` in composer.json due to Filament v4 beta status
- **Enhanced Testing**: Includes comprehensive test suite for v4 compatibility

## Migration from v3

If you're upgrading from the Filament v3 version of this package:

1. Update your `composer.json` minimum stability to `beta`
2. Update the package: `composer update alareqi/filament-translatable-fields`
3. No code changes are required - the API remains the same

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [Ayman Alareqi](https://github.com/AymanAlareqi)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
