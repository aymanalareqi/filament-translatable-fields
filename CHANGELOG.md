# Changelog

All notable changes to `filament-translatable-fields` will be documented in this file.

## 2.0.1 - 2025-XX-XX - Laravel 13 Support

### Added
- **Laravel 13 Support**: Full compatibility with Laravel 13

### Changed
- **Updated Dependencies**: Now supports Laravel 10, 11, 12, and 13

### Migration Guide
1. Run `composer update alareqi/filament-translatable-fields`

## 2.0.0 - 2024-XX-XX - Filament v4 Support

### Added
- **Filament v4 Support**: Complete compatibility with Filament v4 (beta)
- **Unified Schema Components**: Updated to use `Filament\Schemas\Components\Tabs\Tab` namespace
- **Enhanced Testing**: Comprehensive test suite for v4 compatibility
- **Laravel 12 Support**: Full compatibility with Laravel 10, 11, and 12
- **Static Service Provider Name**: Added static `$name` property for v4 compatibility

### Changed
- **Updated Dependencies**: Now requires Filament v4.0+ exclusively
- **Minimum Stability**: Changed to `beta` to support Filament v4 beta packages
- **Component Namespaces**: Updated all component imports to use v4 schema structure
- **Tab Components**: Unified Tab usage across Forms and Infolists using v4 schema

### Breaking Changes
- **Filament Version**: Now requires Filament v4.0+ (no longer compatible with v3)
- **PHP Version**: Minimum PHP version is now 8.2
- **Composer Stability**: Requires `minimum-stability: beta` for Filament v4 beta support
- **Component Namespaces**: Tab components now use unified v4 schema namespace

### Migration Guide
1. Update `composer.json` to set `minimum-stability: beta`
2. Run `composer update alareqi/filament-translatable-fields`
3. No code changes required - API remains the same
