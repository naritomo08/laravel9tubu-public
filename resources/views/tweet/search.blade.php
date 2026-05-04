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

        <x-tweet.image-modal />
    </x-layout.single>
</x-layout>

@push('css')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush
