<?php

namespace Alareqi\FilamentTranslatableFields\Tests\Feature;

use Alareqi\FilamentTranslatableFields\FilamentTranslatableFieldsPlugin;
use Alareqi\FilamentTranslatableFields\Tests\TestCase;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Panel;
use Filament\Schemas\Components\Tabs;
use Filament\Tables\Columns\TextColumn;

class TranslatableFieldsTest extends TestCase
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
    
    public function test_field_can_be_made_translatable()
    {
        $field = TextInput::make('title')
            ->label('Title')
            ->translatable();
            
        $this->assertInstanceOf(Tabs::class, $field);
    }
    
    public function test_field_translatable_with_custom_locales()
    {
        $customLocales = [
            'en' => 'English',
            'de' => 'German'
        ];
        
        $field = TextInput::make('title')
            ->label('Title')
            ->translatable(true, $customLocales);
            
        $this->assertInstanceOf(Tabs::class, $field);
    }
    
    public function test_field_can_be_disabled_from_translatable()
    {
        $field = TextInput::make('title')
            ->label('Title')
            ->translatable(false);
            
        $this->assertInstanceOf(TextInput::class, $field);
    }
    
    public function test_entry_can_be_made_translatable()
    {
        $entry = TextEntry::make('title')
            ->label('Title')
            ->translatable();
            
        $this->assertInstanceOf(Tabs::class, $entry);
    }
    
    public function test_column_can_be_made_translatable()
    {
        $column = TextColumn::make('title')
            ->label('Title')
            ->translatable();
            
        $this->assertInstanceOf(TextColumn::class, $column);
    }
}
