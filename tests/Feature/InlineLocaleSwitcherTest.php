<?php

namespace Alareqi\FilamentTranslatableFields\Tests\Feature;

use Alareqi\FilamentTranslatableFields\FilamentTranslatableFieldsPlugin;
use Alareqi\FilamentTranslatableFields\Tests\TestCase;
use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\FilamentServiceProvider;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Panel;
use Filament\Schemas\Schema;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Livewire\Component;
use Livewire\Livewire;
use Livewire\LivewireServiceProvider;

class InlineLocaleSwitcherTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            SupportServiceProvider::class,
            LivewireServiceProvider::class,
            SchemasServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            ActionsServiceProvider::class,
            NotificationsServiceProvider::class,
            TablesServiceProvider::class,
            FilamentServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));

        FilamentTranslatableFieldsPlugin::make()
            ->supportedLocales(['en' => 'English', 'ar' => 'العربية'])
            ->localeFlags(['en' => '🇬🇧', 'ar' => '🇸🇦'])
            ->boot($this->createMock(Panel::class));
    }

    public function test_switchers_render_inside_inputs_and_the_rich_editor_toolbar(): void
    {
        Livewire::test(InlineTranslationsForm::class)
            ->assertSeeHtml('translatable-input-suffix')
            ->assertSeeHtml('translatable-locale-trigger')
            ->assertSeeHtml('translatable-textarea-control')
            ->assertSeeHtml('fi-fo-rich-editor-toolbar')
            ->assertSeeHtml('data-locale-option="ar"')
            ->assertSeeHtml('dir="rtl"')
            ->assertSeeHtml('dir="ltr"')
            ->assertSee('🇸🇦')
            ->assertSee('original suffix');
    }

    public function test_both_translations_and_nested_repeater_values_are_saved(): void
    {
        Livewire::test(InlineTranslationsForm::class)
            ->set('data.title.en', 'English title')
            ->set('data.title.ar', 'عنوان عربي')
            ->set('data.body.en', ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'English content']]]]])
            ->set('data.body.ar', ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'محتوى عربي']]]]])
            ->set('data.summary.en', 'English summary')
            ->set('data.summary.ar', 'ملخص عربي')
            ->set('data.links', [['label' => ['en' => 'Link', 'ar' => 'رابط']]])
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('saved.title', ['en' => 'English title', 'ar' => 'عنوان عربي'])
            ->assertSet('saved.body', ['en' => '<p>English content</p>', 'ar' => '<p>محتوى عربي</p>'])
            ->assertSet('saved.summary', ['en' => 'English summary', 'ar' => 'ملخص عربي'])
            ->assertSet('saved.links.0.label', ['en' => 'Link', 'ar' => 'رابط']);
    }

    public function test_locale_specific_validation_still_validates_hidden_languages(): void
    {
        Livewire::test(InlineTranslationsForm::class)
            ->set('data.title.en', 'English title')
            ->set('data.title.ar', '')
            ->call('save')
            ->assertHasErrors(['data.title.ar' => 'required'])
            ->assertSet('saved', []);
    }

    public function test_custom_locale_closures_are_resolved_and_labels_are_escaped(): void
    {
        $field = TextInput::make('title')->translatable(true, fn (): array => ['ar' => '<script>alert(1)</script>']);
        $localeField = $field->getDefaultChildComponents()[0]->getDefaultChildComponents()[0];

        $this->assertSame('title.ar', $localeField->getName());
        $html = view('alareqi-filament-translatable-fields::locale-switcher', [
            'locale' => 'ar',
            'options' => ['ar' => ['label' => '<script>alert(1)</script>', 'tab' => 'title-ar-tab', 'flag' => null]],
        ])->render();
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }
}

class InlineTranslationsForm extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public ?array $data = [];

    public array $saved = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->schema([
            TextInput::make('title')->label('Title')->suffix('original suffix')
                ->localeValidation(fn (string $locale): array => $locale === 'ar' ? ['required'] : ['nullable'])
                ->translatable(),
            RichEditor::make('body')->toolbarButtons(['bold', 'italic'])->translatable(),
            Textarea::make('summary')->translatable(),
            Repeater::make('links')->schema([TextInput::make('label')->translatable()]),
        ]);
    }

    public function save(): void
    {
        $this->saved = $this->form->getState();
    }

    public function render(): string
    {
        return '<div><form wire:submit="save">{{ $this->form }}<button type="submit">Save</button></form></div>';
    }
}
