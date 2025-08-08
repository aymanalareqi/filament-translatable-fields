# Changelog

All notable changes to `filament-translatable-fields` will be documented in this file.

## 2.0.0 - 2024-XX-XX

### Added
- Added support for Filament v4 (beta)
- Added support for Laravel 12
- Updated minimum PHP version requirement to 8.2
- Added basic test structure
- Added static `$name` property to service provider for Filament v4 compatibility

### Changed
- Updated composer.json to support both Filament v3 and v4
- Changed minimum stability to beta to support Filament v4 beta packages
- Fixed duplicate return statement in Entry macro

### Breaking Changes
- Minimum PHP version is now 8.2 (was 8.0)
- Package now requires minimum stability "beta" for Filament v4 support
