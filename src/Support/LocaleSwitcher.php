<?php

namespace Alareqi\FilamentTranslatableFields\Support;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\RichEditorTool;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Contracts\View\View;

class LocaleSwitcher
{
    /** @param array<string, array{label: string, tab: string, flag: ?string}> $options */
    public static function configure(Field $field, string $locale, array $options): void
    {
        $data = ['locale' => $locale, 'options' => $options];
        $direction = in_array(strtolower(explode('-', str_replace('_', '-', $locale))[0]), ['ar', 'fa', 'he', 'ur', 'ps', 'sd', 'ug', 'yi', 'dv', 'ckb'], true) ? 'rtl' : 'ltr';

        if (method_exists($field, 'extraInputAttributes')) {
            $field->extraInputAttributes(['dir' => $direction, 'lang' => $locale], merge: true);
        }

        if ($field instanceof TextInput) {
            $original = $field->getClone();
            $field->suffix(fn (TextInput $component): View => view('alareqi-filament-translatable-fields::input-suffix', [
                ...$data,
                'originalSuffix' => $original->container($component->getContainer())->getSuffixLabel(),
            ]), isInline: true);

            return;
        }

        if ($field instanceof RichEditor) {
            $original = $field->getClone();
            $field->tools([
                RichEditorTool::make('translationLocale')->view('alareqi-filament-translatable-fields::locale-switcher', $data),
            ])->toolbarButtons(fn (RichEditor $component): array => [
                ...$original->container($component->getContainer())->getToolbarButtons(),
                ['translationLocale'],
            ]);
            $field->hint(fn (RichEditor $component): View => view('alareqi-filament-translatable-fields::hint', [
                ...$data,
                'originalHint' => $original->container($component->getContainer())->getHint(),
                'showSwitcher' => $component->isDisabled(),
            ]));

            return;
        }

        if ($field instanceof Textarea && $field->getCustomFieldWrapperView() === null) {
            $field->viewData(['translationSwitcher' => $data])
                ->fieldWrapperView('alareqi-filament-translatable-fields::textarea-wrapper');

            return;
        }

        $original = $field->getClone();
        $field->hint(fn (Field $component): View => view('alareqi-filament-translatable-fields::hint', [
            ...$data,
            'originalHint' => $original->container($component->getContainer())->getHint(),
            'showSwitcher' => true,
        ]));
    }
}
