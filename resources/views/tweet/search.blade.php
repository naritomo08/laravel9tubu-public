<x-layout :title="'検索 | ' . config('app.name', 'Laravel')">
    <x-layout.single>
        <h2 class="text-center text-blue-500 text-4xl font-bold mt-8 mb-8">
            つぶやき検索
        </h2>

        @if (session('feedback.success'))
            <x-alert.success>{{ session('feedback.success') }}</x-alert.success>
        @endif

        <div class="mb-4 text-right">
            <x-element.button-a :href="route('tweet.index')">
                TOPへ戻る
            </x-element.button-a>
        </div>

        <div
            class="bg-white rounded-md shadow-lg p-4 dark:bg-gray-900 dark:shadow-gray-950/40"
            data-tweet-search
            data-search-url="{{ route('tweet.search.results', [], false) }}"
            data-users-url="{{ route('tweet.search.users', [], false) }}"
        >
            <label for="tweet-search-query" class="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-200">
                検索キーワード
            </label>
            <input
                id="tweet-search-query"
                type="search"
                name="q"
                value="{{ $query }}"
                maxlength="200"
                autocomplete="off"
                placeholder="検索キーワード"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-400 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                data-tweet-search-input
            >
            <label for="tweet-user-search" class="mt-3 inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                <input
                    id="tweet-user-search"
                    type="checkbox"
                    name="user_search"
                    value="1"
                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-400 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:border-gray-700 dark:bg-gray-950"
                    data-tweet-user-search-input
                    @checked($userSearch)
                >
                <span>ユーザー検索</span>
            </label>
            <div class="mt-3 {{ $userSearch ? '' : 'hidden' }}" data-tweet-user-select-wrap>
                <label for="tweet-search-user-id" class="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-200">
                    検索するユーザー
                </label>
                <select
                    id="tweet-search-user-id"
                    name="user_id"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-400 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                    data-tweet-user-select
                    data-selected-user-id="{{ $selectedUserId }}"
                >
                    <option value="">ユーザーを選択</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected($selectedUserId === $user->id)>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="mt-3 flex items-center justify-between gap-3 text-sm text-gray-500 dark:text-gray-400">
                <span data-tweet-search-count>{{ count($tweets) }}件</span>
                <span class="hidden text-blue-500 dark:text-blue-300" data-tweet-search-loading>検索中...</span>
            </div>
        </div>

        <div
            class="bg-white rounded-md shadow-lg mt-5 mb-5 dark:bg-gray-900 dark:shadow-gray-950/40"
            data-tweet-list
            data-like-status-url="{{ route('like.status', [], false) }}"
            data-tweet-search-results
        >
            <x-tweet.search-results :tweets="$tweets" :returnUrl="$returnUrl"></x-tweet.search-results>
        </div>

        <div x-data="{ imgModal : false, imgModalSrc : '' }">
            <div
                @img-modal.window="imgModal = true; imgModalSrc = $event.detail.imgModalSrc;"
                x-cloak
                x-show="imgModal"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform"
                x-transition:enter-end="opacity-100 transform"
                x-transition:leave="transition ease-in duration-300"
                x-transition:leave-start="opacity-100 transform"
                x-transition:leave-end="opacity-0 transform"
                x-on:click.away="imgModalSrc = ''"
                class="p-2 fixed w-full h-100 inset-0 z-50 overflow-hidden flex justify-center items-center bg-black bg-opacity-75">
                <div @click.away="imgModal = false" class="flex flex-col max-w-3xl max-h-full overflow-auto">
                    <div class="z-50">
                        <button @click="imgModal = false" class="float-right pt-2 pr-2 outline-none focus:outline-none">
                            <svg class="fill-current text-white h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                    <div class="p-2">
                        <img
                            class="object-contain h-1/2-screen"
                            :alt="imgModalSrc"
                            :src="imgModalSrc">
                    </div>
                </div>
            </div>
        </div>
    </x-layout.single>
</x-layout>

@push('css')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush
