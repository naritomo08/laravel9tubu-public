@props([
    'tweet',
    'returnPage' => 1,
    'returnUrl' => null,
])
<div class="p-4">
    <form action="{{ route('tweet.update.put', ['tweetId' => $tweet->id]) }}" method="post" enctype="multipart/form-data">
        @method('PUT')
        @csrf
        <input type="hidden" name="page" value="{{ $returnPage }}">
        @if($returnUrl)
            <input type="hidden" name="return_url" value="{{ $returnUrl }}">
        @endif
        <div class="mt-1">
            <textarea
                name="tweet"
                rows="3"
                class="focus:ring-blue-400 focus:border-blue-400 mt-1 block w-full sm:text-sm border border-gray-300 rounded-md p-2 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:placeholder-gray-500"
                placeholder="つぶやきを入力">{{ $tweet->content }}</textarea>
        </div>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            140文字まで
        </p>

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
        @error('images')
        <x-alert.error>{{ $message }}</x-alert.error>
        @enderror
        @error('images.*')
        <x-alert.error>{{ $message }}</x-alert.error>
        @enderror
        @error('delete_image_ids')
        <x-alert.error>{{ $message }}</x-alert.error>
        @enderror

        <div class="flex flex-wrap justify-end">
            <x-element.button>
                編集
            </x-element.button>
        </div>
    </form>
</div>
