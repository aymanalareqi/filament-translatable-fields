<x-filament::dropdown
    placement="bottom-end"
    :shift="true"
    class="translatable-locale-switcher"
    x-init="$nextTick(() => $refs.panel.setAttribute('role', 'menu'))"
    x-on:click.stop=""
>
    <x-slot name="trigger">
        <button
            type="button"
            x-on:mousedown.stop=""
            x-on:click.stop="toggle($event)"
            class="translatable-locale-trigger"
            aria-label="{{ __('alareqi-filament-translatable-fields::messages.change_language') }}: {{ $options[$locale]['label'] }}"
            aria-haspopup="menu"
            data-locale="{{ $locale }}"
        >
            <span>{{ strtoupper($locale) }}</span>
            <x-filament::icon icon="heroicon-m-chevron-down" class="translatable-locale-chevron" />
        </button>
    </x-slot>

    <div class="translatable-locale-options" role="group" aria-label="{{ __('alareqi-filament-translatable-fields::messages.change_language') }}">
        @foreach ($options as $code => $option)
            <button
                type="button"
                role="menuitemradio"
                aria-checked="{{ $code === $locale ? 'true' : 'false' }}"
                class="translatable-locale-option {{ $code === $locale ? 'translatable-locale-option-selected' : '' }}"
                @isset($columnName)
                    x-on:click.stop="close(); $wire.setTranslatableColumnLocale(@js($columnName), @js($code))"
                @else
                    x-on:click="tab = @js($option['tab']); close()"
                @endisset
                data-locale-option="{{ $code }}"
            >
                @if ($option['flag'])
                    <span aria-hidden="true" class="translatable-locale-flag">{{ $option['flag'] }}</span>
                @endif
                <span class="translatable-locale-code" title="{{ $option['label'] }}">{{ strtoupper($code) }}</span>
                <span class="translatable-locale-name">{{ $option['label'] }}</span>
                @if ($code === $locale)
                    <x-filament::icon icon="heroicon-m-check" class="translatable-locale-check" />
                @endif
            </button>
        @endforeach
    </div>
</x-filament::dropdown>
