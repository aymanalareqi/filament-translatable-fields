# Filament Translatable Fields

[![Latest Version on Packagist](https://img.shields.io/packagist/v/alareqi/filament-translatable-fields.svg?style=flat-square)](https://packagist.org/packages/alareqi/filament-translatable-fields)
[![Total Downloads](https://img.shields.io/packagist/dt/alareqi/filament-translatable-fields.svg?style=flat-square)](https://packagist.org/packages/alareqi/filament-translatable-fields)

This package adds a way to make all filament fields translatable.
It uses the `spatie/laravel-translatable` package in the background.

Compatible with:
- Laravel 10, 11, and 12
- Filament 3.x and 4.x
- PHP 8.2+

## Installation

You can install the package via composer:

```bash
composer require alareqi/filament-translatable-fields
```

### Filament v4 Support

This package supports both Filament v3 and v4. For Filament v4 (currently in beta), you may need to set your composer minimum stability to beta:

```bash
composer config minimum-stability beta
```

Or add this to your `composer.json`:

```json
{
    "minimum-stability": "beta"
}
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

This package will substitute the original field with a `Filament\Forms\Components\Tabs` component. This component will render the original field for each locale.

All chained methods you add before calling `->translatable()` will be applied to the original field.
All chained methods you add after calling `->translatable()` will be applied to the `Filament\Forms\Components\Tabs` component.

## Upgrading to v2.0 (Filament v4 Support)

### Requirements

- PHP 8.2 or higher
- Laravel 10, 11, or 12
- Filament 3.x or 4.x

### For Filament v4 Users

If you want to use Filament v4 (currently in beta), you need to configure Composer to allow beta packages:

```bash
composer config minimum-stability beta
```

Then update the package:

```bash
composer update alareqi/filament-translatable-fields
```

### For Filament v3 Users

No changes are required. The package continues to work with Filament v3 without any modifications to your code.

### Breaking Changes

- **PHP Version**: Minimum PHP version is now 8.2 (previously 8.0)
- **Composer Stability**: Package now requires minimum stability "beta" for Filament v4 support

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [Ayman Alareqi](https://github.com/AymanAlareqi)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
