
@props([
    'wireProp',
    'value',
    'options' => [],
    'groups' => null,
    'placeholder' => 'Pilih...',
    'disabled' => false,
    'size' => 'md',
    'ariaLabel' => null,
    'error' => false,
])

@php
    $flat = [];

    foreach (($groups ?? [['label' => null, 'options' => $options]]) as $group) {
        foreach ($group['options'] as $option) {
            $flat[] = array_merge([
                'disabled' => false,
                'group' => $group['label'],
            ], (array) $option);
        }
    }

    $selected = collect($flat)->first(
        fn ($option) => (string) $option['value'] === (string) $value
    );

    $hasValue = $selected !== null;

    $groupedList = collect($flat)
        ->groupBy(fn ($option) => $option['group'] ?? '');
@endphp

<div
    {{ $attributes->merge(['class' => 'relative']) }}
    x-data="{
        open: false,
        search: '',

        matches(label) {
            return String(label)
                .toLowerCase()
                .includes(this.search.trim().toLowerCase());
        },

        reset() {
            this.search = '';
        }
    }"
    @click.outside="open = false; reset()"
    @keydown.escape.window="open = false; reset()"
    x-cloak
>
    {{-- Trigger --}}
    <button
        type="button"
        x-ref="trigger"
        x-on:click="
            if (!open) {
                open = true;
                search = '';
                $nextTick(() => $refs.searchInput.focus());
            } else {
                open = false;
                search = '';
            }
        "
        :aria-expanded="open"
        aria-haspopup="listbox"
        class="ina-select-dropdown__trigger ina-select-dropdown__trigger--size-{{ $size }}
            {{ $error ? 'ina-select-dropdown__trigger--status-error' : '' }}
            {{ $disabled ? 'ina-select-dropdown__trigger--disabled' : '' }}"
        @if ($disabled) disabled tabindex="-1" @endif
        @if ($ariaLabel !== null) aria-label="{{ $ariaLabel }}" @endif
    >
        <span
            class="ina-select-dropdown__trigger-text text-sm!
                {{ $hasValue
                    ? 'ina-select-dropdown__trigger-text--selected'
                    : 'ina-select-dropdown__trigger-text--placeholder' }}"
        >
            {{ $selected['label'] ?? $placeholder }}
        </span>

        <i
            class="ti ti-chevron-down ina-select-dropdown__trigger-icon
                {{ $disabled ? 'ina-select-dropdown__trigger-icon--disabled' : '' }}"
            :class="open ? 'rotate-180' : ''"
            aria-hidden="true"
        ></i>
    </button>

    {{-- Dropdown Panel --}}
    <div
        class="ina-select-dropdown__panel absolute top-[calc(100%+4px)] left-0 z-50 w-full max-h-72
            !transition-opacity duration-300"
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-1"
        x-on:click.stop
        role="listbox"
        x-cloak
    >
        {{-- Search Input --}}
        <div class="sticky top-0 z-10 p-2 bg-white">
            <input
                type="text"
                x-ref="searchInput"
                x-model="search"
                placeholder="Cari opsi..."
                aria-label="Cari opsi"
                class="w-full rounded-md border border-gray-200 px-3 py-2 text-sm
                    outline-none focus:border-guide-300 focus:ring-1 focus:ring-guide-300"
                autocomplete="off"
            />
        </div>

        {{-- Options --}}
        <div class="ina-select-dropdown__options overflow-y-auto">
            @foreach ($groupedList as $groupLabel => $items)
                <div
                    x-show="{{ 'true' }}"
                >
                    {{-- Group Label --}}
                    @if ($groupLabel !== '')
                        <p
                            class="px-3 pt-2 pb-1 text-[11px] font-semibold text-content-tertiary"
                            role="presentation"
                            x-show="[
                                @foreach ($items as $option)
                                    {{ \Illuminate\Support\Js::from((string) $option['label']) }},
                                @endforeach
                            ].some(label => matches(label))"
                        >
                            {{ $groupLabel }}
                        </p>
                    @endif

                    {{-- Group Options --}}
                    @foreach ($items as $option)
                        @php
                            $isSelected = $hasValue
                                && (string) $selected['value'] === (string) $option['value'];
                        @endphp

                        <button
                            type="button"
                            role="option"
                            aria-selected="{{ $isSelected ? 'true' : 'false' }}"
                            x-show="matches({{ \Illuminate\Support\Js::from((string) $option['label']) }})"
                            x-on:click="open = false; reset()"
                            wire:click="$set(
                                {{ \Illuminate\Support\Js::from($wireProp) }},
                                {{ \Illuminate\Support\Js::from($option['value']) }}
                            )"
                            class="ina-select-dropdown__option
                                {{ $isSelected ? 'ina-select-dropdown__option--selected-single' : '' }}
                                {{ $option['disabled'] ? 'ina-select-dropdown__option--disabled' : '' }}"
                            @if ($option['disabled']) disabled @endif
                        >
                            <span class="min-w-0 truncate">
                                {{ $option['label'] }}
                            </span>

                            @if ($isSelected)
                                <i
                                    class="ti ti-check ml-auto shrink-0 text-sm"
                                    aria-hidden="true"
                                ></i>
                            @endif
                        </button>
                    @endforeach
                </div>
            @endforeach

            {{-- Empty State --}}
            <p
                x-cloak
                x-show="
                    !Array.from(
                        $el.closest('.ina-select-dropdown__options')
                            .querySelectorAll('[role=option]')
                    ).some(el =>
                        matches(el.textContent.trim()) &&
                        !el.disabled
                    )
                "
                class="px-2 py-2 text-xs text-content-tertiary text-center"
            >
                Tidak ada hasil ditemukan
            </p>
        </div>
    </div>
</div>
