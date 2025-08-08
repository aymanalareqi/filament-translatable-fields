# Upgrading to Filament v4

This guide will help you upgrade from the Filament v3 version of this package to the Filament v4 version.

## Requirements

Before upgrading, ensure your project meets these requirements:

- **Filament v4.0+** (currently in beta)
- **Laravel 10, 11, or 12**
- **PHP 8.2+**

## Step-by-Step Upgrade

### 1. Update Composer Configuration

First, update your `composer.json` to allow beta packages:

```json
{
    "minimum-stability": "beta",
    "prefer-stable": true
}
```

Or run this command:

```bash
composer config minimum-stability beta
```

### 2. Update the Package

Update the package to the v4 version:

```bash
composer update alareqi/filament-translatable-fields
```

### 3. Verify Installation

Run the verification script to ensure everything is properly configured:

```bash
composer run verify-v4
```

## What Changed

### Dependencies
- **Filament**: Now requires v4.0+ (was v3.2+)
- **PHP**: Now requires 8.2+ (was 8.2+, no change)
- **Composer**: Now requires `minimum-stability: beta`

### Component Namespaces
- **Tab Components**: Now use unified `Filament\Schemas\Components\Tabs\Tab` namespace
- **Forms/Infolists**: Both now use the same Tab component from the schema namespace

### Internal Changes
- Updated service provider with static `$name` property for v4 compatibility
- Enhanced test suite for v4 compatibility
- Updated documentation for v4

## No Code Changes Required

The great news is that **no code changes are required** in your application! The API remains exactly the same:

```php
// This still works exactly the same
TextInput::make('title')
    ->label('Title')
    ->translatable();

// Custom locales still work the same way
TextInput::make('description')
    ->label('Description')
    ->translatable(true, [
        'en' => 'English',
        'ar' => 'العربية',
        'fr' => 'French'
    ]);
```

## Testing

After upgrading, test your translatable fields to ensure they work correctly:

1. **Forms**: Verify that translatable fields render with tabs
2. **Infolists**: Check that translatable entries display properly
3. **Tables**: Confirm that translatable columns show the correct locale
4. **Locale Switching**: Test that the locale selector works in tabs

## Troubleshooting

### Common Issues

**Issue**: "Class 'Filament\Schemas\Components\Tabs\Tab' not found"
**Solution**: Ensure you have Filament v4 installed and `minimum-stability: beta` set

**Issue**: Composer conflicts during update
**Solution**: Try `composer update --with-all-dependencies`

**Issue**: Tests failing
**Solution**: Run `composer run test` to see specific test failures

### Getting Help

If you encounter issues during the upgrade:

1. Check the [GitHub Issues](https://github.com/aymanalareqi/filament-translatable-fields/issues)
2. Run the verification script: `composer run verify-v4`
3. Check that Filament v4 is properly installed in your project

## Rollback

If you need to rollback to the v3 version:

1. Switch to the v3 branch or tag
2. Update your `composer.json` to use Filament v3
3. Run `composer update`

```bash
# Example rollback
composer require "alareqi/filament-translatable-fields:^1.0" "filament/filament:^3.2"
composer config minimum-stability dev
```
