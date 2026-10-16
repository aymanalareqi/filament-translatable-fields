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
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Panel;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;
use Livewire\Livewire;
use Livewire\LivewireServiceProvider;
use Spatie\Translatable\HasTranslations;

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

    public function test_shared_required_rules_validate_every_locale(): void
    {
        Livewire::test(FluentTranslationsForm::class)
            ->set('data.title.en', '')
            ->set('data.title.ar', '')
            ->call('save')
            ->assertHasErrors(['data.title.en' => 'required', 'data.title.ar' => 'required'])
            ->assertSet('saved', []);
    }

    public function test_shared_length_rules_are_preserved_on_locale_fields(): void
    {
        Livewire::test(FluentTranslationsForm::class)
            ->set('data.title.en', 'Eleven chars')
            ->call('save')
            ->assertHasErrors(['data.title.en' => 'max'])
            ->assertHasNoErrors(['data.title.ar'])
            ->assertSet('saved', []);
    }

    public function test_locale_configuration_can_make_a_shared_required_field_optional(): void
    {
        Livewire::test(FluentTranslationsForm::class)
            ->set('data.summary.ar', '')
            ->set('data.summary.en', '')
            ->call('save')
            ->assertHasErrors(['data.summary.ar' => 'required'])
            ->assertHasNoErrors(['data.summary.en'])
            ->assertSet('saved', []);
    }

    public function test_locale_length_overrides_do_not_leak_to_other_locales(): void
    {
        Livewire::test(FluentTranslationsForm::class)
            ->set('data.summary.ar', 'مل')
            ->set('data.summary.en', 'Eleven chars')
            ->call('save')
            ->assertHasErrors(['data.summary.ar' => 'min', 'data.summary.en' => 'max'])
            ->assertSet('saved', []);
    }

    public function test_locale_configuration_validates_nested_fields_with_custom_locales(): void
    {
        Livewire::test(FluentTranslationsForm::class)
            ->set('data.links', [['label' => ['ar' => '', 'de' => '']]])
            ->call('save')
            ->assertHasErrors(['data.links.0.label.ar' => 'required'])
            ->assertHasNoErrors(['data.links.0.label.de'])
            ->assertSet('saved', []);
    }

    public function test_optional_locales_and_configured_fields_can_be_saved(): void
    {
        Livewire::test(FluentTranslationsForm::class)
            ->set('data.summary.ar', 'ملخص عربي طويل')
            ->set('data.links', [['label' => ['ar' => 'رابط', 'de' => '']]])
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('saved.title.en', 'Title')
            ->assertSet('saved.summary.ar', 'ملخص عربي طويل')
            ->assertSet('saved.body.ar', '<p>محتوى عربي</p>')
            ->assertSet('saved.links.0.label.ar', 'رابط');
    }

    public function test_locale_configuration_validates_required_rich_editor_content(): void
    {
        Livewire::test(FluentTranslationsForm::class)
            ->set('data.body.ar', null)
            ->call('save')
            ->assertHasErrors(['data.body.ar' => 'required'])
            ->assertHasNoErrors(['data.body.en'])
            ->assertSet('saved', []);
    }

    public function test_standard_validation_closures_receive_the_locale_field(): void
    {
        Livewire::test(FluentTranslationsForm::class)
            ->set('data.conditional.ar', '')
            ->call('save')
            ->assertHasErrors(['data.conditional.ar' => 'required'])
            ->assertHasNoErrors(['data.conditional.en'])
            ->assertSet('saved', []);
    }

    public function test_infolists_render_translations_with_the_shared_switcher_and_preserve_hints(): void
    {
        Livewire::test(InlineTranslationsInfolist::class)
            ->assertSeeHtml('translatable-locale-trigger')
            ->assertSeeHtml('title-en-tab')
            ->assertSeeHtml('body-en-tab')
            ->assertSee('Original entry hint')
            ->assertSee('English title')
            ->assertSee('عنوان عربي')
            ->assertSeeHtml('<strong>English body</strong>')
            ->assertSeeHtml('<strong>محتوى عربي</strong>')
            ->assertSee('🇸🇦')
            ->assertDontSeeHtml('translatable-field-locale-select')
            ->assertDontSee('Title [English]');
    }

    public function test_infolist_custom_locale_closures_escape_locale_labels(): void
    {
        Livewire::test(InlineTranslationsInfolist::class)
            ->set('untrustedLabels', true)
            ->assertSee('English title')
            ->assertSeeHtml('&lt;script&gt;alert(1)&lt;/script&gt;')
            ->assertDontSeeHtml('<script>alert(1)</script>');
    }

    public function test_infolist_translation_can_be_disabled(): void
    {
        $entry = TextEntry::make('title');

        $this->assertSame($entry, $entry->translatable(false));
        $this->assertSame($entry, $entry->translatable(fn (): bool => false));
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

class FluentTranslationsForm extends InlineTranslationsForm
{
    public function mount(): void
    {
        $this->form->fill([
            'title' => ['en' => 'Title', 'ar' => 'عنوان'],
            'summary' => ['ar' => 'ملخص'],
            'body' => ['ar' => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'محتوى عربي']]]]]],
            'conditional' => ['ar' => 'مطلوب'],
            'links' => [],
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->schema([
            TextInput::make('title')->required()->maxLength(10)->translatable(),
            Textarea::make('summary')->required()->maxLength(20)
                ->translatable(configure: fn (Textarea $field, string $locale): Textarea => $field
                    ->required($locale === 'ar')
                    ->minLength($locale === 'ar' ? 3 : 2)
                    ->maxLength($locale === 'ar' ? 20 : 10)),
            RichEditor::make('body')->required()->toolbarButtons(['bold', 'italic'])
                ->translatable(configure: fn (RichEditor $field, string $locale): RichEditor => $field->required($locale === 'ar')),
            TextInput::make('conditional')
                ->required(fn (TextInput $component): bool => str_ends_with($component->getName(), '.ar'))
                ->translatable(),
            Repeater::make('links')->defaultItems(0)->schema([
                TextInput::make('label')->maxLength(10)->translatable(
                    customLocales: ['ar' => 'العربية', 'de' => 'German'],
                    configure: function (TextInput $field, string $locale): void {
                        $field->required($locale === 'ar');
                    },
                ),
            ]),
        ]);
    }
}

class InlineTranslationsInfolist extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public bool $untrustedLabels = false;

    public function infolist(Schema $schema): Schema
    {
        return $schema->record(new InfolistTranslationRecord([
            'title' => ['en' => 'English title', 'ar' => 'عنوان عربي'],
            'body' => ['en' => '<strong>English body</strong>', 'ar' => '<strong>محتوى عربي</strong>'],
        ]))->schema([
            TextEntry::make('title')->label('Title')->hint('Original entry hint')->translatable(),
            TextEntry::make('body')->label('Body')->html()->translatable(customLocales: fn (): array => [
                'en' => $this->untrustedLabels ? '<script>alert(1)</script>' : 'English',
                'ar' => 'العربية',
            ]),
        ]);
    }

    public function render(): string
    {
        return '<div>{{ $this->infolist }}</div>';
    }
}

class InfolistTranslationRecord extends Model
{
    use HasTranslations;

    protected $guarded = [];

    public array $translatable = ['title', 'body'];
}
