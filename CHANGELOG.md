# Changelog

All notable changes to `filament-translatable-fields` will be documented in this file.

## Unreleased

### Added

- Independent language dropdowns in translatable table-column headers, with selected-locale display, search, and sorting for Eloquent attributes and relationships.
- `InteractsWithTranslatableColumns` trait for resource list pages, relation managers, and standalone Livewire tables, with validated locale changes and pagination reset.
- Shared language dropdown for form fields and infolist entries, using the panel's primary colors and dark mode styles.
- Optional locale flags configured through the panel plugin's `localeFlags()` method.
- Form-field `translatable(configure: ...)` callback receiving each localized field clone and its locale code.
- Closure-based custom locales for form fields and infolist entries.
- Dependency support for Filament 5 alongside Filament 4.

### Changed

- Standard fluent validation methods are retained on each locale clone, including `required()`. Use the `configure` callback when only selected locales should be required.
- Text inputs display the switcher alongside their suffix, textareas display it inside the control, and rich editors display it in the toolbar. Other form fields and infolist entries display it beside the label.
- Translated entries retain their original labels and hints, and use independent language targets.
- Existing `localeValidation()` declarations remain supported; the README now documents fluent validation as the primary API.

### Fixed

- Table-column `translatable()` now translates cell values and supports custom locales instead of only appending a locale to the heading.
- Dropdown anchoring in rich editor toolbars and visibility inside text input wrappers.
- Textarea switcher spacing to match text inputs.
- Escaping of infolist locale labels and resolution of custom locale closures.
- Infolist `translatable(false)` now returns the original entry without a translation wrapper.
- Nested menu roles that prevented Filament's keyboard navigation from finding language options.

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
