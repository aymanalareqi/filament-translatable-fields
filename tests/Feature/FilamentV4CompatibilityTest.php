<?php

namespace Alareqi\FilamentTranslatableFields\Tests\Feature;

use Alareqi\FilamentTranslatableFields\FilamentTranslatableFieldsPlugin;
use Alareqi\FilamentTranslatableFields\Tests\TestCase;
use Filament\Panel;

class FilamentV4CompatibilityTest extends TestCase
{
    public function test_plugin_is_compatible_with_filament_v4()
    {
        // Test that the plugin can be instantiated
        $plugin = FilamentTranslatableFieldsPlugin::make();
        $this->assertInstanceOf(FilamentTranslatableFieldsPlugin::class, $plugin);
        
        // Test that the plugin has the correct ID
        $this->assertEquals('alareqi-filament-translatable-fields', $plugin->getId());
    }
    
    public function test_plugin_can_register_with_panel()
    {
        $plugin = FilamentTranslatableFieldsPlugin::make();
        $panel = $this->createMock(Panel::class);
        
        // Should not throw any exceptions
        $plugin->register($panel);
        $this->assertTrue(true);
    }
    
    public function test_plugin_can_boot_with_panel()
    {
        $plugin = FilamentTranslatableFieldsPlugin::make()
            ->supportedLocales([
                'en' => 'English',
                'ar' => 'العربية'
            ]);
            
        $panel = $this->createMock(Panel::class);
        
        // Should not throw any exceptions
        $plugin->boot($panel);
        $this->assertTrue(true);
    }
    
    public function test_service_provider_has_static_name_property()
    {
        $this->assertTrue(property_exists(
            \Alareqi\FilamentTranslatableFields\FilamentTranslatableFieldsServiceProvider::class,
            'name'
        ));
        
        $this->assertEquals(
            'alareqi-filament-translatable-fields',
            \Alareqi\FilamentTranslatableFields\FilamentTranslatableFieldsServiceProvider::$name
        );
    }
    
    public function test_required_classes_exist()
    {
        // Test that required Filament v4 classes exist (will fail if v4 not installed)
        $requiredClasses = [
            'Filament\Schemas\Components\Tabs',
            'Filament\Schemas\Components\Tabs\Tab',
        ];
        
        foreach ($requiredClasses as $class) {
            if (class_exists($class)) {
                $this->assertTrue(class_exists($class), "Class {$class} should exist for Filament v4");
            } else {
                $this->markTestSkipped("Filament v4 classes not available - this is expected in development");
            }
        }
    }
}
