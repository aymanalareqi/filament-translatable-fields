<?php

namespace Alareqi\FilamentTranslatableFields\Tests\Unit;

use Alareqi\FilamentTranslatableFields\FilamentTranslatableFieldsPlugin;
use Alareqi\FilamentTranslatableFields\Tests\TestCase;

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
        
        $locales = $plugin->getSupportedLocales();
        
        $this->assertIsArray($locales);
        $this->assertNotEmpty($locales);
        $this->assertContains('en', $locales);
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
    
    public function test_plugin_supports_closure_for_locales()
    {
        $plugin = FilamentTranslatableFieldsPlugin::make();
        
        $plugin->supportedLocales(function () {
            return [
                'en' => 'English',
                'es' => 'Spanish'
            ];
        });
        
        $locales = $plugin->getSupportedLocales();
        
        $this->assertEquals([
            'en' => 'English',
            'es' => 'Spanish'
        ], $locales);
    }
}
