@props([
    'tweet',
    'returnPage' => 1,
])
<div class="p-4">
    <form action="{{ route('tweet.update.put', ['tweetId' => $tweet->id]) }}" method="post" enctype="multipart/form-data">
        @method('PUT')
        @csrf
        <input type="hidden" name="page" value="{{ $returnPage }}">
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

        @error('tweet')
        <x-alert.error>{{ $message }}</x-alert.error>
        @enderror

        @if($tweet->images->count() > 0)
            <div class="mt-4">
                <p class="text-sm font-semibold text-gray-600 dark:text-gray-300">現在の画像</p>
                <div class="mt-2 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    @foreach($tweet->images as $image)
                        <label class="block rounded-md border border-gray-200 p-2 dark:border-gray-700">
                            <img
                                alt="{{ $image->name }}"
                                class="aspect-square w-full rounded object-cover"
                                src="{{ asset('storage/images/' . $image->name) }}"
                            >
                            <span class="mt-2 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                                <input
                                    type="checkbox"
                                    name="delete_image_ids[]"
                                    value="{{ $image->id }}"
                                    class="rounded border-gray-300 text-red-600 focus:ring-red-500"
                                >
                                削除する
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="mt-4">
            <p class="text-sm font-semibold text-gray-600 dark:text-gray-300">追加する画像</p>
            <x-tweet.form.images></x-tweet.form.images>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                画像は合計4枚まで
            </p>
        </div>

        @error('images')
        <x-alert.error>{{ $message }}</x-alert.error>
        @enderror

        <div class="flex flex-wrap justify-end">
            <x-element.button>
                編集
            </x-element.button>
        </div>
    </form>
</div>
