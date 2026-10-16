@php
    $sortable = $column->isSortable() && ! $isReordering;
    $sorted = $column->getTable()->getSortColumn() === $columnName;
    $direction = $column->getTable()->getSortDirection();
    $alignment = $column->getAlignment();
    $width = $column->getWidth();
    $tooltip = $column->getHeaderTooltip();
@endphp

<th
    scope="col"
    @if ($sortable)
        aria-sort="{{ $sorted ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none' }}"
    @endif
    {{ $column->getExtraHeaderAttributeBag()->class([
        'fi-ta-header-cell',
        'fi-ta-header-cell-'.str($columnName)->camel()->kebab(),
        'fi-growable' => blank($width) && $column->canGrow(default: false),
        'fi-grouped' => $column->getGroup(),
        'fi-wrapped' => $column->canHeaderWrap(),
        'fi-ta-header-cell-sorted' => $sorted,
        $alignment instanceof \Filament\Support\Enums\Alignment ? 'fi-align-'.$alignment->value : (is_string($alignment) ? $alignment : ''),
        filled($column->getHiddenFrom()) ? $column->getHiddenFrom().':fi-hidden' : '',
        filled($column->getVisibleFrom()) ? $column->getVisibleFrom().':fi-visible' : '',
    ])->style(['width: '.e($width) => filled($width)]) }}
>
    <span class="translatable-column-label" data-translatable-column="{{ $columnName }}">
        @if ($sortable)
            <button
                type="button"
                wire:click="sortTable('{{ $columnName }}')"
                wire:target="sortTable('{{ $columnName }}')"
                class="fi-ta-header-cell-sort-btn"
            >
                <span
                    @if (filled($tooltip))
                        @if ($tooltip instanceof \Illuminate\Contracts\Support\Htmlable)
                            x-tooltip.html="{ content: @js($tooltip->toHtml()), theme: $store.theme }"
                        @else
                            x-tooltip="{ content: @js($tooltip), theme: $store.theme }"
                        @endif
                    @endif
                >{{ $column->getLabel() }}</span>
                {{
                    \Filament\Support\generate_icon_html(
                        $sorted && $direction === 'asc' ? \Filament\Support\Icons\Heroicon::ChevronUp : \Filament\Support\Icons\Heroicon::ChevronDown,
                        alias: match (true) {
                            $sorted && $direction === 'asc' => \Filament\Tables\View\TablesIconAlias::HEADER_CELL_SORT_ASC_BUTTON,
                            $sorted && $direction === 'desc' => \Filament\Tables\View\TablesIconAlias::HEADER_CELL_SORT_DESC_BUTTON,
                            default => \Filament\Tables\View\TablesIconAlias::HEADER_CELL_SORT_BUTTON,
                        },
                        attributes: new \Filament\Support\View\ComponentAttributeBag([
                            'wire:loading.remove.delay.'.config('filament.livewire_loading_delay', 'default') => true,
                            'wire:target' => "sortTable('{$columnName}')",
                        ]),
                    )
                }}
                {{
                    \Filament\Support\generate_loading_indicator_html(new \Filament\Support\View\ComponentAttributeBag([
                        'wire:loading.delay.'.config('filament.livewire_loading_delay', 'default') => '',
                        'wire:target' => "sortTable('{$columnName}')",
                    ]))
                }}
            </button>
        @else
            <span
                @if (filled($tooltip))
                    @if ($tooltip instanceof \Illuminate\Contracts\Support\Htmlable)
                        x-tooltip.html="{ content: @js($tooltip->toHtml()), theme: $store.theme }"
                    @else
                        x-tooltip="{ content: @js($tooltip), theme: $store.theme }"
                    @endif
                @endif
            >{{ $column->getLabel() }}</span>
        @endif

        <span class="translatable-column-switcher" x-on:click.stop="" x-on:keydown.stop="">
            @include('alareqi-filament-translatable-fields::locale-switcher')
        </span>
    </span>
</th>
