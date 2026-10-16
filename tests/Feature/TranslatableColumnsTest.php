<?php

namespace Alareqi\FilamentTranslatableFields\Tests\Feature;

use Alareqi\FilamentTranslatableFields\Concerns\InteractsWithTranslatableColumns;
use Alareqi\FilamentTranslatableFields\FilamentTranslatableFieldsPlugin;
use Alareqi\FilamentTranslatableFields\Tests\TestCase;
use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Panel;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\TablesServiceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Livewire\LivewireServiceProvider;
use Spatie\Translatable\HasTranslations;

class TranslatableColumnsTest extends TestCase
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
        config()->set('translatable.fallback_locale', 'en');
        FilamentTranslatableFieldsPlugin::make()
            ->supportedLocales(['en' => 'English', 'ar' => 'العربية'])
            ->localeFlags(['en' => '🇬🇧', 'ar' => '🇸🇦'])
            ->boot($this->createMock(Panel::class));

        Schema::create('translation_column_categories', function (Blueprint $table): void {
            $table->id();
            $table->json('name');
        });
        Schema::create('translation_column_records', function (Blueprint $table): void {
            $table->id();
            $table->json('title');
            $table->json('summary');
            $table->string('slug');
            $table->unsignedBigInteger('category_id')->nullable();
        });
    }

    /** @param array<string, mixed> $attributes */
    protected function record(array $attributes = []): TranslationColumnRecord
    {
        return TranslationColumnRecord::query()->create([
            'title' => ['en' => 'English title', 'ar' => 'عنوان عربي', 'fr' => 'Titre français'],
            'summary' => ['en' => 'English summary', 'ar' => 'ملخص عربي'],
            'slug' => 'fixture',
            ...$attributes,
        ]);
    }

    public function test_each_column_can_switch_independently_without_changing_the_app_locale(): void
    {
        $record = $this->record();

        Livewire::test(TranslatedColumnsTable::class)
            ->assertSeeHtml('translatable-column-switcher')
            ->assertSeeHtml('data-locale-option="ar"')
            ->assertSee('🇸🇦')
            ->assertTableColumnStateSet('title', 'English title', $record)
            ->call('setTranslatableColumnLocale', 'title', 'ar')
            ->assertSet('translatableColumnLocales.title', 'ar')
            ->assertTableColumnStateSet('title', 'عنوان عربي', $record)
            ->assertTableColumnStateSet('summary', 'English summary', $record)
            ->assertSeeHtml('dir="rtl"');

        $this->assertSame('en', app()->getLocale());
        $this->assertSame('English title', $record->refresh()->getTranslation('title', 'en'));
    }

    public function test_global_search_uses_the_selected_locale(): void
    {
        $english = $this->record(['title' => ['en' => 'Needle', 'ar' => 'آخر']]);
        $arabic = $this->record(['title' => ['en' => 'Other', 'ar' => 'Needle']]);

        Livewire::test(TranslatedColumnsTable::class)
            ->searchTable('Needle')
            ->assertCanSeeTableRecords([$english])
            ->assertCanNotSeeTableRecords([$arabic])
            ->call('setTranslatableColumnLocale', 'title', 'ar')
            ->assertCanSeeTableRecords([$arabic])
            ->assertCanNotSeeTableRecords([$english]);
    }

    public function test_individual_search_uses_the_selected_column_locale(): void
    {
        $english = $this->record(['summary' => ['en' => 'Needle', 'ar' => 'آخر']]);
        $arabic = $this->record(['summary' => ['en' => 'Other', 'ar' => 'Needle']]);

        Livewire::test(TranslatedColumnsTable::class)
            ->searchTableColumns(['summary' => 'Needle'])
            ->assertCanSeeTableRecords([$english])
            ->assertCanNotSeeTableRecords([$arabic])
            ->call('setTranslatableColumnLocale', 'summary', 'ar')
            ->assertCanSeeTableRecords([$arabic])
            ->assertCanNotSeeTableRecords([$english]);
    }

    public function test_sorting_uses_the_selected_locale(): void
    {
        $first = $this->record(['title' => ['en' => 'Alpha', 'ar' => 'Zulu']]);
        $second = $this->record(['title' => ['en' => 'Zulu', 'ar' => 'Alpha']]);

        Livewire::test(TranslatedColumnsTable::class)
            ->sortTable('title', 'asc')->assertCanSeeTableRecords([$first, $second], inOrder: true)
            ->call('setTranslatableColumnLocale', 'title', 'ar')
            ->assertCanSeeTableRecords([$second, $first], inOrder: true)
            ->sortTable('title', 'desc')->assertCanSeeTableRecords([$first, $second], inOrder: true);
    }

    public function test_relationship_columns_display_search_and_sort_the_selected_locale(): void
    {
        $firstCategory = TranslationColumnCategory::query()->create(['name' => ['en' => 'Alpha', 'ar' => 'Zulu']]);
        $secondCategory = TranslationColumnCategory::query()->create(['name' => ['en' => 'Zulu', 'ar' => 'Alpha']]);
        $first = $this->record(['category_id' => $firstCategory->id]);
        $second = $this->record(['category_id' => $secondCategory->id]);

        Livewire::test(TranslatedColumnsTable::class)
            ->assertTableColumnStateSet('category.name', 'Alpha', $first)
            ->sortTable('category.name', 'asc')->assertCanSeeTableRecords([$first, $second], inOrder: true)
            ->call('setTranslatableColumnLocale', 'category.name', 'ar')
            ->assertTableColumnStateSet('category.name', 'Zulu', $first)
            ->assertCanSeeTableRecords([$second, $first], inOrder: true)
            ->searchTableColumns(['category.name' => 'Zulu'])
            ->assertCanSeeTableRecords([$first])->assertCanNotSeeTableRecords([$second]);
    }

    public function test_changing_locale_resets_the_current_page(): void
    {
        $this->record();
        $this->record();

        Livewire::test(TranslatedColumnsTable::class)
            ->set('tableRecordsPerPage', 1)
            ->call('setPage', 2)
            ->assertSet('paginators.page', 2)
            ->call('setTranslatableColumnLocale', 'title', 'ar')
            ->assertSet('paginators.page', 1);
    }

    public function test_locale_choices_are_isolated_between_table_instances(): void
    {
        $record = $this->record();
        Livewire::test(TranslatedColumnsTable::class)->call('setTranslatableColumnLocale', 'title', 'ar');

        Livewire::test(TranslatedColumnsTable::class)
            ->assertSet('translatableColumnLocales', [])
            ->assertTableColumnStateSet('title', 'English title', $record);
    }

    public function test_unsupported_app_locale_falls_back_to_the_first_configured_locale(): void
    {
        $record = $this->record();
        app()->setLocale('de');

        Livewire::test(TranslatedColumnsTable::class)->assertTableColumnStateSet('title', 'English title', $record);
    }

    public function test_custom_locale_closures_and_untrusted_labels_are_supported(): void
    {
        $record = $this->record();

        Livewire::test(CustomLocalesColumnsTable::class)
            ->assertSeeHtml('data-locale="fr"')
            ->assertDontSeeHtml('<script>alert(1)</script>')
            ->assertSeeHtml('&lt;script&gt;alert(1)&lt;/script&gt;')
            ->assertTableColumnStateSet('title', 'Titre français', $record)
            ->call('setTranslatableColumnLocale', 'title', 'en')->assertStatus(422);
    }

    public function test_missing_translations_do_not_silently_display_another_locale(): void
    {
        $record = $this->record(['title' => ['en' => 'English only']]);

        Livewire::test(TranslatedColumnsTable::class)
            ->call('setTranslatableColumnLocale', 'title', 'ar')
            ->assertTableColumnStateSet('title', '', $record)
            ->searchTable('English only')->assertCanNotSeeTableRecords([$record]);
    }

    public function test_unknown_columns_and_locales_cannot_be_requested(): void
    {
        foreach ([['title', 'de'], ['unknown', 'ar'], ['id', 'ar'], ['title', "en' OR 1=1 --"]] as [$column, $locale]) {
            Livewire::test(TranslatedColumnsTable::class)
                ->call('setTranslatableColumnLocale', $column, $locale)
                ->assertStatus(422);
        }
        $this->assertSame(0, TranslationColumnRecord::query()->count());
    }

    public function test_locale_state_cannot_be_overwritten_from_the_browser(): void
    {
        $this->expectException(CannotUpdateLockedPropertyException::class);

        Livewire::test(TranslatedColumnsTable::class)->set('translatableColumnLocales.title', 'de');
    }

    public function test_existing_custom_search_and_sort_callbacks_are_preserved(): void
    {
        $first = $this->record(['slug' => 'first']);
        $second = $this->record(['slug' => 'second']);

        Livewire::test(CustomQueriesColumnsTable::class)
            ->searchTable('first')->assertCanSeeTableRecords([$first])->assertCanNotSeeTableRecords([$second])
            ->searchTable('')->sortTable('title')->assertCanSeeTableRecords([$second, $first], inOrder: true);
    }

    public function test_column_formatting_and_attributes_are_preserved(): void
    {
        $record = $this->record();

        Livewire::test(TranslatedColumnsTable::class)
            ->assertTableColumnFormattedStateSet('summary', 'Summary: English summary', $record)
            ->assertSeeHtml('data-original-column="summary"')
            ->call('setTranslatableColumnLocale', 'summary', 'ar')
            ->assertTableColumnFormattedStateSet('summary', 'Summary: ملخص عربي', $record);
    }

    public function test_column_manager_labels_do_not_contain_language_controls(): void
    {
        $component = Livewire::test(TranslatedColumnsTable::class);

        $this->assertSame('Title', $component->get('tableColumns')[1]['label']);
        $this->assertSame('Summary', $component->get('tableColumns')[2]['label']);
    }

    public function test_columns_without_the_trait_use_the_app_locale_without_rendering_an_unusable_switcher(): void
    {
        $record = $this->record();
        app()->setLocale('ar');

        Livewire::test(PlainTranslationsTable::class)
            ->assertTableColumnStateSet('title', 'عنوان عربي', $record)
            ->assertDontSeeHtml('translatable-column-switcher');
    }

    public function test_translatable_false_keeps_an_ordinary_column(): void
    {
        $this->assertSame('Title', TextColumn::make('title')->label('Title')->translatable(false)->getLabel());
    }
}

class PlainTranslationsTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table->query(TranslationColumnRecord::query())->columns([
            TextColumn::make('id')->sortable(),
            TextColumn::make('title')->label('Title')->searchable(isIndividual: true)->sortable()->translatable(),
            TextColumn::make('summary')->label(fn (): string => 'Summary')
                ->formatStateUsing(fn (string $state): string => 'Summary: '.$state)
                ->extraAttributes(['data-original-column' => 'summary'])
                ->searchable(isIndividual: true, isGlobal: false)->sortable()
                ->translatable(customLocales: fn (): array => ['en' => 'English', 'ar' => 'العربية']),
            TextColumn::make('category.name')->label('Category')
                ->searchable(isIndividual: true, isGlobal: false)->sortable()->translatable(),
        ])->paginated([1, 10]);
    }

    public function render(): string
    {
        return '<div>{{ $this->table }}</div>';
    }
}

class TranslatedColumnsTable extends PlainTranslationsTable
{
    use InteractsWithTranslatableColumns;
}

class CustomLocalesColumnsTable extends TranslatedColumnsTable
{
    public function table(Table $table): Table
    {
        return $table->query(TranslationColumnRecord::query())->columns([
            TextColumn::make('title')->label('<script>alert(1)</script>')->sortable()
                ->translatable(customLocales: fn (): array => ['fr' => '<script>alert(1)</script>']),
        ]);
    }
}

class CustomQueriesColumnsTable extends TranslatedColumnsTable
{
    public function table(Table $table): Table
    {
        return $table->query(TranslationColumnRecord::query())->columns([
            TextColumn::make('title')->searchable(query: fn (Builder $query, string $search): Builder => $query->where('slug', $search))
                ->sortable(query: fn (Builder $query): Builder => $query->orderByDesc('id'))->translatable(),
        ]);
    }
}

class TranslationColumnRecord extends Model
{
    use HasTranslations;

    public $timestamps = false;

    protected $guarded = [];

    public array $translatable = ['title', 'summary'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(TranslationColumnCategory::class, 'category_id');
    }
}

class TranslationColumnCategory extends Model
{
    use HasTranslations;

    public $timestamps = false;

    protected $guarded = [];

    public array $translatable = ['name'];
}
