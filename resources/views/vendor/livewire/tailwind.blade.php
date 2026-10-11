@php
if (! isset($scrollTo)) {
    $scrollTo = 'body';
}

$scrollIntoViewJsSnippet = ($scrollTo !== false)
    ? <<<JS
       (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()
    JS
    : '';

$btnBase = 'inline-flex h-9 min-w-9 items-center justify-center rounded-md border border-stroke-primary bg-background-primary px-2.5 text-[13px] text-content-secondary transition-colors duration-150 hover:border-stroke-secondary hover:bg-background-secondary hover:text-content-primary';
$btnDisabled = 'inline-flex h-9 min-w-9 items-center justify-center rounded-md border border-stroke-primary bg-background-primary text-[13px] text-content-tertiary opacity-60 cursor-default';
$btnActive = 'inline-flex h-9 min-w-9 items-center justify-center rounded-md border border-guide-600 bg-guide-600 text-[13px] font-medium text-white';
$btnSquare = 'inline-flex h-9 w-9 items-center justify-center rounded-md border border-stroke-primary bg-background-primary text-content-secondary transition-colors duration-150 hover:border-stroke-secondary hover:bg-background-secondary hover:text-content-primary';
@endphp

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex flex-col items-center justify-between gap-3 sm:flex-row">
    {{-- Info hasil --}}
    <div class="hidden sm:block">
        <p class="text-sm text-content-secondary">
            Menampilkan
            <span class="font-medium text-content-primary">{{ $paginator->firstItem() }}</span>
            –
            <span class="font-medium text-content-primary">{{ $paginator->lastItem() }}</span>
            dari
            <span class="font-medium text-content-primary">{{ $paginator->total() }}</span>
            data
        </p>
    </div>

    {{-- Tombol: mobile (prev/next) --}}
    <div class="flex items-center gap-2 sm:hidden">
        @if ($paginator->onFirstPage())
            <span class="{{ $btnDisabled }}" aria-disabled="true">
                <i class="ti ti-chevron-left text-base" aria-hidden="true"></i>
            </span>
        @else
            <button
                type="button"
                wire:click="previousPage('{{ $paginator->getPageName() }}')"
                x-on:click="{{ $scrollIntoViewJsSnippet }}"
                wire:loading.attr="disabled"
                class="{{ $btnSquare }}"
                aria-label="Halaman sebelumnya"
            >
                <i class="ti ti-chevron-left text-base" aria-hidden="true"></i>
            </button>
        @endif

        <span class="text-[13px] text-content-secondary">
            {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
        </span>

        @if ($paginator->hasMorePages())
            <button
                type="button"
                wire:click="nextPage('{{ $paginator->getPageName() }}')"
                x-on:click="{{ $scrollIntoViewJsSnippet }}"
                wire:loading.attr="disabled"
                class="{{ $btnSquare }}"
                aria-label="Halaman berikutnya"
            >
                <i class="ti ti-chevron-right text-base" aria-hidden="true"></i>
            </button>
        @else
            <span class="{{ $btnDisabled }}" aria-disabled="true">
                <i class="ti ti-chevron-right text-base" aria-hidden="true"></i>
            </span>
        @endif
    </div>

    {{-- Tombol: desktop (prev + angka + next) --}}
    <div class="hidden items-center gap-1 text-[13px] select-none sm:flex">
        {{-- Prev --}}
        @if ($paginator->onFirstPage())
            <span class="{{ $btnDisabled }}" aria-disabled="true" aria-label="Halaman sebelumnya">
                <i class="ti ti-chevron-left text-base" aria-hidden="true"></i>
            </span>
        @else
            <button
                type="button"
                wire:click="previousPage('{{ $paginator->getPageName() }}')"
                x-on:click="{{ $scrollIntoViewJsSnippet }}"
                wire:loading.attr="disabled"
                class="{{ $btnSquare }}"
                aria-label="Halaman sebelumnya"
            >
                <i class="ti ti-chevron-left text-base" aria-hidden="true"></i>
            </button>
        @endif

        {{-- Pagination Elements --}}
        @foreach ($elements as $element)
            {{-- "Three Dots" Separator --}}
            @if (is_string($element))
                <span class="flex h-9 w-9 items-center justify-center text-content-tertiary" aria-hidden="true">
                    {{ $element }}
                </span>
            @endif

            {{-- Array Of Links --}}
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    <span wire:key="paginator-{{ $paginator->getPageName() }}-page{{ $page }}">
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="{{ $btnActive }}">
                                {{ $page }}
                            </span>
                        @else
                            <button
                                type="button"
                                wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                                x-on:click="{{ $scrollIntoViewJsSnippet }}"
                                data-loading:opacity-50
                                class="{{ $btnBase }}"
                                aria-label="Ke halaman {{ $page }}"
                            >
                                {{ $page }}
                            </button>
                        @endif
                    </span>
                @endforeach
            @endif
        @endforeach

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            <button
                type="button"
                wire:click="nextPage('{{ $paginator->getPageName() }}')"
                x-on:click="{{ $scrollIntoViewJsSnippet }}"
                wire:loading.attr="disabled"
                class="{{ $btnSquare }}"
                aria-label="Halaman berikutnya"
            >
                <i class="ti ti-chevron-right text-base" aria-hidden="true"></i>
            </button>
        @else
            <span class="{{ $btnDisabled }}" aria-disabled="true" aria-label="Halaman berikutnya">
                <i class="ti ti-chevron-right text-base" aria-hidden="true"></i>
            </span>
        @endif
    </div>
    </nav>
@endif
