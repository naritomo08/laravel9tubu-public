@props([
    'tweet',
    'returnPage' => 1,
    'returnUrl' => null,
])
@php
    $tweetMaxLength = config('tweet.content_max_length');
@endphp
<div class="p-4">
    <form action="{{ route('tweet.update.put', ['tweetId' => $tweet->id]) }}" method="post" enctype="multipart/form-data">
        @method('PUT')
        @csrf
        <input type="hidden" name="page" value="{{ $returnPage }}">
        @if($returnUrl)
            <input type="hidden" name="return_url" value="{{ $returnUrl }}">
        @endif
        <x-tweet.form.content :value="old('tweet', $tweet->content)" :maxLength="$tweetMaxLength"></x-tweet.form.content>
        <label class="mt-3 inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
            <input type="hidden" name="is_secret" value="0">
            <input
                type="checkbox"
                name="is_secret"
                value="1"
                @checked(old('is_secret', $tweet->is_secret))
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900"
            >
            <span class="inline-flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-amber-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v2H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-1V6a4 4 0 00-4-4zm2 6V6a2 2 0 10-4 0v2h4z" clip-rule="evenodd" />
                </svg>
                シークレットモード
            </span>
        </label>

        @if($tweet->scheduled_at?->isFuture())
            <label class="mt-3 block text-sm text-gray-700 dark:text-gray-200">
                <span class="block mb-1">予約日時</span>
                <input
                    type="datetime-local"
                    name="scheduled_at"
                    value="{{ old('scheduled_at', $tweet->scheduled_at?->format('Y-m-d\TH:i')) }}"
                    min="{{ now()->format('Y-m-d\TH:i') }}"
                    class="focus:ring-blue-400 focus:border-blue-400 block w-full sm:text-sm border border-gray-300 rounded-md p-2 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                >
            </label>
        @endif

        @php
            $visibleImages = $tweet->images->filter->existsOnPublicDisk();
        @endphp

        @if($visibleImages->isNotEmpty())
            <div class="mt-4">
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">現在の画像</p>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    @foreach($visibleImages as $image)
                        <label class="mt-2 block rounded-md border border-gray-200 p-2 dark:border-gray-700">
                            <img src="{{ $image->publicUrl() }}" alt="{{ $image->name }}" class="h-24 w-full rounded object-cover">
                            <span class="mt-2 flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                <input type="checkbox" name="delete_image_ids[]" value="{{ $image->id }}" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                                削除する
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="mt-4">
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">追加する画像</p>
            <x-tweet.form.images></x-tweet.form.images>
        </div>

        @error('tweet')
        <x-alert.error>{{ $message }}</x-alert.error>
        @enderror
        @error('is_secret')
        <x-alert.error>{{ $message }}</x-alert.error>
        @enderror
        @error('scheduled_at')
        <x-alert.error>{{ $message }}</x-alert.error>
        @enderror
        @error('images')
        <x-alert.error>{{ $message }}</x-alert.error>
        @enderror
        @error('images.*')
        <x-alert.error>{{ $message }}</x-alert.error>
        @enderror
        @error('delete_image_ids')
        <x-alert.error>{{ $message }}</x-alert.error>
        @enderror
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            4枚まで
        </p>

        <div class="flex flex-wrap justify-end">
            <x-element.button data-tweet-submit>
                編集
            </x-element.button>
        </div>
    </form>
</div>
