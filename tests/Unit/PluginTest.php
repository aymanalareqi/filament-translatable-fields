<?php

namespace Alareqi\FilamentTranslatableFields\Tests\Unit;

use Alareqi\FilamentTranslatableFields\FilamentTranslatableFieldsPlugin;
use PHPUnit\Framework\TestCase;

class PluginTest extends TestCase
{
    public function test_plugin_can_be_instantiated()
    {
        $plugin = FilamentTranslatableFieldsPlugin::make();
        
        $this->assertInstanceOf(FilamentTranslatableFieldsPlugin::class, $plugin);
        $this->assertEquals('alareqi-filament-translatable-fields', $plugin->getId());
    }
    
    public function test_plugin_has_default_supported_locales()
    {
        $plugin = FilamentTranslatableFieldsPlugin::make();
        
        // Mock config to return 'en' as default locale
        $locales = $plugin->getSupportedLocales();
        
        $this->assertIsArray($locales);
        $this->assertNotEmpty($locales);
    }
    
    public function test_plugin_can_set_supported_locales()
    {
        $plugin = FilamentTranslatableFieldsPlugin::make();
        
        $customLocales = [
            'en' => 'English',
            'ar' => 'العربية',
            'fr' => 'French'
        ];
        
        $plugin->supportedLocales($customLocales);
        
        $this->assertEquals($customLocales, $plugin->getSupportedLocales());
    }
}
