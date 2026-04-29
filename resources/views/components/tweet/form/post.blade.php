@props([
    'currentPage' => 1
])

@if(Auth::user() && Auth::user()->hasVerifiedEmail())
<div class="p-4">
    <form action="{{ route('tweet.create') }}" method="post" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="page" value="{{ $currentPage }}">
        <div class="mt-1">
            <textarea
                name="tweet"
                rows="3"
                class="focus:ring-blue-400 focus:border-blue-400 mt-1 block w-full sm:text-sm border border-gray-300 rounded-md p-2 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:placeholder-gray-500"
                placeholder="つぶやきを入力"></textarea>
        </div>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            140文字まで
        </p>
        <label class="mt-3 inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
            <input type="hidden" name="is_secret" value="0">
            <input
                type="checkbox"
                name="is_secret"
                value="1"
                @checked(old('is_secret'))
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900"
            >
            <span class="inline-flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-amber-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v2H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-1V6a4 4 0 00-4-4zm2 6V6a2 2 0 10-4 0v2h4z" clip-rule="evenodd" />
                </svg>
                シークレットモード
            </span>
        </label>
        <x-tweet.form.images></x-tweet.form.images>

        @error('tweet')
        <x-alert.error>{{ $message }}</x-alert.error>
        @enderror
        @error('is_secret')
        <x-alert.error>{{ $message }}</x-alert.error>
        @enderror
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            4枚までで、トータル1MB
        </p>

        <div class="flex flex-wrap justify-end">
            <x-element.button>
                つぶやく
            </x-element.button>
        </div>
    </form>
</div>
@endif
@guest
<div class="flex flex-wrap justify-center">
    <div class="w-1/2 p-4 flex flex-wrap justify-evenly">
        <x-element.button-a :href="route('login')">ログイン</x-element.button-a>
        <x-element.button-a :href="route('register')" theme="secondary">会員登録</x-element.button-a>
    </div>
</div>
@endguest
