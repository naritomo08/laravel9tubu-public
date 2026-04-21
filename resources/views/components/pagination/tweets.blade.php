@if ($paginator->hasPages())
    <nav
        role="navigation"
        aria-label="{{ __('Pagination Navigation') }}"
        class="flex flex-col items-center gap-3"
    >
        <p class="text-sm text-gray-600">
            {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }} ページ
            <span class="mx-1 text-gray-400">|</span>
            {{ $paginator->total() }} 件
        </p>

        <div class="flex flex-wrap items-center justify-center gap-2">
            @if ($paginator->onFirstPage())
                <span class="inline-flex min-w-[2.5rem] justify-center rounded-md border border-gray-200 bg-gray-100 px-3 py-2 text-sm font-medium text-gray-400">
                    前へ
                </span>
            @else
                <a
                    href="{{ $paginator->previousPageUrl() }}"
                    rel="prev"
                    class="inline-flex min-w-[2.5rem] justify-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:border-blue-400 hover:text-blue-600"
                    aria-label="{{ __('pagination.previous') }}"
                >
                    前へ
                </a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="inline-flex min-w-[2.5rem] justify-center px-1 py-2 text-sm text-gray-400">
                        {{ $element }}
                    </span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span
                                aria-current="page"
                                class="inline-flex min-w-[2.5rem] justify-center rounded-md border border-blue-500 bg-blue-500 px-3 py-2 text-sm font-semibold text-white"
                            >
                                {{ $page }}
                            </span>
                        @else
                            <a
                                href="{{ $url }}"
                                class="inline-flex min-w-[2.5rem] justify-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:border-blue-400 hover:text-blue-600"
                                aria-label="{{ __('Go to page :page', ['page' => $page]) }}"
                            >
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a
                    href="{{ $paginator->nextPageUrl() }}"
                    rel="next"
                    class="inline-flex min-w-[2.5rem] justify-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:border-blue-400 hover:text-blue-600"
                    aria-label="{{ __('pagination.next') }}"
                >
                    次へ
                </a>
            @else
                <span class="inline-flex min-w-[2.5rem] justify-center rounded-md border border-gray-200 bg-gray-100 px-3 py-2 text-sm font-medium text-gray-400">
                    次へ
                </span>
            @endif
        </div>
    </nav>
@endif
