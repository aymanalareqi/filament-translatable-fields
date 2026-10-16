<x-filament-forms::field-wrapper
    :field="$field"
    :label-prefix="$labelPrefix ?? null"
    :label-suffix="$labelSuffix ?? null"
    :has-errors="$hasErrors ?? true"
    :error-message="$errorMessage ?? null"
    :error-messages="$errorMessages ?? null"
    {{ $attributes }}
>
    <div class="translatable-textarea-control">
        {{ $slot }}
        <div class="translatable-textarea-switcher">
            @include('alareqi-filament-translatable-fields::locale-switcher', $field->getViewData()['translationSwitcher'])
        </div>
    </div>
</x-filament-forms::field-wrapper>
