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

$selected = collect($flat)->firstWhere('value', (string) $value);
$hasValue = $selected !== null;
@endphp

<div
    {{ $attributes->merge(['class' => 'relative']) }}
    x-data="{ open: false }"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
    x-cloak
>
    {{-- Trigger --}}
    <button
        type="button"
        x-ref="trigger"
        x-on:click="open = !open"
        :aria-expanded="open"
        aria-haspopup="listbox"
        class="ina-select-dropdown__trigger ina-select-dropdown__trigger--size-{{ $size }}
            {{ $error ? 'ina-select-dropdown__trigger--status-error' : '' }}
            {{ $disabled ? 'ina-select-dropdown__trigger--disabled' : '' }}"
        @if ($disabled) disabled tabindex="-1" @endif
        @if ($ariaLabel !== null) aria-label="{{ $ariaLabel }}" @endif
    >
        <span
            class="ina-select-dropdown__trigger-text {{ $hasValue ? 'ina-select-dropdown__trigger-text--selected' : 'ina-select-dropdown__trigger-text--placeholder' }}"
        >{{ $selected['label'] ?? $placeholder }}</span>
        <i
            class="ti ti-chevron-down ina-select-dropdown__trigger-icon {{ $disabled ? 'ina-select-dropdown__trigger-icon--disabled' : '' }}"
            :class="open ? 'rotate-180' : ''"
            aria-hidden="true"
        ></i>
    </button>

    {{-- Panel --}}
    <div
        class="ina-select-dropdown__panel absolute top-[calc(100%+4px)] left-0 z-50 w-full max-h-72"
        x-show="open"
        x-transition.opacity.duration.150ms
        role="listbox"
    >
        <div class="ina-select-dropdown__options overflow-y-auto">
            @php
                $groupedList = collect($flat)
                    ->groupBy(fn ($option) => $option['group'] ?? '')
                    ->map(fn ($items) => $items->all());
            @endphp
            @foreach ($groupedList as $groupLabel => $items)
                @if ($groupLabel !== '')
                    <p class="px-3 pt-2 pb-1 text-[11px] font-semibold text-content-tertiary" role="presentation">
                        {{ $groupLabel }}
                    </p>
                @endif
                @foreach ($items as $option)
                    @php $isSelected = $hasValue && $selected['value'] === $option['value']; @endphp
                    <button
                        type="button"
                        role="option"
                        aria-selected="{{ $isSelected ? 'true' : 'false' }}"
                        wire:click="$set({{ \Js::from($wireProp) }}, {{ \Js::from($option['value']) }})"
                        x-on:click="open = false"
                        class="ina-select-dropdown__option
                            {{ $isSelected ? 'ina-select-dropdown__option--selected-single' : '' }}
                            {{ $option['disabled'] ? 'ina-select-dropdown__option--disabled' : '' }}"
                        @if ($option['disabled']) disabled @endif
                    >
                        <span class="min-w-0 truncate">{{ $option['label'] }}</span>
                        @if ($isSelected)
                            <i class="ti ti-check ml-auto shrink-0 text-sm" aria-hidden="true"></i>
                        @endif
                    </button>
                @endforeach
            @endforeach
        </div>
    </div>
</div>
