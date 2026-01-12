<?php

namespace Alareqi\FilamentTranslatableFields\Tests\Feature;

use Alareqi\FilamentTranslatableFields\FilamentTranslatableFieldsPlugin;
use Alareqi\FilamentTranslatableFields\Tests\TestCase;
use Filament\Forms\Components\TextInput;
use Filament\Panel;
use Filament\Schemas\Components\Tabs;

class LocaleValidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Register the plugin
        $plugin = FilamentTranslatableFieldsPlugin::make()
            ->supportedLocales([
                'en' => 'English',
                'ar' => 'العربية',
                'fr' => 'French'
            ]);

        // Mock panel for plugin boot
        $panel = $this->createMock(Panel::class);
        $plugin->boot($panel);
    }

    public function test_locale_validation_can_be_added_to_field()
    {
        $field = TextInput::make('title')
            ->label('Title')
            ->localeValidation(function ($locale) {
                return match ($locale) {
                    'en' => ['required', 'min:3'],
                    'ar' => ['required', 'min:2'],
                    default => ['nullable']
                };
            })
            ->translatable();

        $this->assertInstanceOf(Tabs::class, $field);
    }

    public function test_locale_validation_with_all_required()
    {
        $field = TextInput::make('title')
            ->label('Title')
            ->localeValidation(function ($locale) {
                return ['required', 'min:3'];
            })
            ->translatable();

        $this->assertInstanceOf(Tabs::class, $field);
    }

    public function test_locale_validation_with_some_optional()
    {
        $field = TextInput::make('description')
            ->label('Description')
            ->localeValidation(function ($locale) {
                return match ($locale) {
                    'en' => ['required', 'max:500'],
                    default => ['nullable', 'max:500']
                };
            })
            ->translatable();

        $this->assertInstanceOf(Tabs::class, $field);
    }

    public function test_locale_validation_with_custom_locales()
    {
        $customLocales = [
            'en' => 'English',
            'de' => 'German'
        ];

        $field = TextInput::make('title')
            ->label('Title')
            ->localeValidation(function ($locale) {
                return match ($locale) {
                    'en' => ['required'],
                    'de' => ['nullable']
                };
            })
            ->translatable(true, $customLocales);

        $this->assertInstanceOf(Tabs::class, $field);
    }

    public function test_translatable_field_without_locale_validation()
    {
        // Ensure backwards compatibility - translatable without localeValidation should still work
        $field = TextInput::make('title')
            ->label('Title')
            ->translatable();

        $this->assertInstanceOf(Tabs::class, $field);
    }

    public function test_locale_validation_with_different_rule_types()
    {
        $field = TextInput::make('email')
            ->label('Email')
            ->localeValidation(function ($locale) {
                return match ($locale) {
                    'en' => ['required', 'email', 'max:255'],
                    'ar' => ['nullable', 'email'],
                    default => ['nullable']
                };
            })
            ->translatable();

        $this->assertInstanceOf(Tabs::class, $field);
    }
}
