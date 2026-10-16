<span
    wire:key="translatable-column-{{ hash('sha256', $columnName) }}-{{ $locale }}"
    class="translatable-column-label"
    data-translatable-column="{{ $columnName }}"
    x-data="{}"
    x-init="$el.closest('.fi-ta-header-cell-sort-btn')?.setAttribute('aria-label', @js(trim(strip_tags($label instanceof \Illuminate\Contracts\Support\Htmlable ? $label->toHtml() : $label))))"
>
    <span>{{ $label }}</span>

    <template x-if="$el.closest('th') !== null">
        <span class="translatable-column-switcher" x-on:keydown.stop="">
            @include('alareqi-filament-translatable-fields::locale-switcher', ['columnName' => $columnName])
        </span>
    </template>
</span>
