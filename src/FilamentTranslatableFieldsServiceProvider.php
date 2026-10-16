<?php

namespace Alareqi\FilamentTranslatableFields;

use Alareqi\FilamentTranslatableFields\Support\TranslatableColumn;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentView;
use Filament\Tables\Columns\Column;
use Filament\Tables\View\TablesRenderHook;
use Illuminate\Contracts\View\View;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentTranslatableFieldsServiceProvider extends PackageServiceProvider
{
    public static string $name = 'alareqi-filament-translatable-fields';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasViews()
            ->hasTranslations()
            ->hasInstallCommand(function (InstallCommand $command) {
                $composerFile = file_get_contents(__DIR__.'/../composer.json');

                if ($composerFile) {
                    $githubRepo = json_decode($composerFile, true)['homepage'] ?? null;

                    if ($githubRepo) {
                        $command
                            ->askToStarRepoOnGitHub($githubRepo);
                    }
                }
            });
    }

    public function packageBooted(): void
    {
        // Asset Registration
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        if (TranslatableColumn::hasHeaderRenderHook()) {
            FilamentView::registerRenderHook(TablesRenderHook::HEADER_CELL, function (array $data): View|string {
                $column = $data['column'] ?? null;
                if (! $column instanceof Column || ! ($configuration = TranslatableColumn::from($column))
                    || ! method_exists($column->getLivewire(), 'setTranslatableColumnLocale')) {
                    return '';
                }

                return view('alareqi-filament-translatable-fields::column-header-cell', [
                    'column' => $column,
                    'isReordering' => $data['isReordering'] ?? false,
                    'columnName' => $column->getName(),
                    'locale' => $configuration->locale($column),
                    'options' => $configuration->options($column),
                ]);
            });
        }
    }

    protected function getAssetPackageName(): ?string
    {
        return 'alareqi/filament-translatable-fields';
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        $styles = Css::make('filament-translatable-fields-styles', __DIR__.'/../resources/dist/translatable-fields.css');

        return [
            $styles->html(fn (): string => $styles->getHref().'&hash='.hash_file('sha256', $styles->getPath())),
        ];
    }
}
