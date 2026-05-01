@props([
    'value' => '',
    'maxLength' => config('tweet.content_max_length'),
])

<div class="mt-1" data-tweet-character-counter data-tweet-max-length="{{ $maxLength }}">
    <div class="relative rounded-md border border-gray-300 bg-white focus-within:border-blue-400 focus-within:ring-1 focus-within:ring-blue-400 dark:border-gray-700 dark:bg-gray-900">
        <div
            class="tweet-content-highlight pointer-events-none absolute inset-0 overflow-hidden whitespace-pre-wrap break-words p-2 pb-8 text-transparent sm:text-sm leading-5 dark:text-transparent"
            data-tweet-highlight
            aria-hidden="true"
        ></div>
        <textarea
            name="tweet"
            rows="3"
            data-tweet-input
            class="relative z-10 block w-full resize-y border-0 bg-transparent p-2 pb-8 sm:text-sm leading-5 text-gray-900 placeholder-gray-500 focus:ring-0 dark:text-gray-100 dark:placeholder-gray-500"
            placeholder="つぶやきを入力">{{ $value }}</textarea>
        <span
            class="pointer-events-none absolute bottom-2 right-2 z-20 rounded bg-white/90 px-1.5 py-0.5 text-xs font-medium text-gray-500 shadow-sm dark:bg-gray-900/90 dark:text-gray-400"
            data-tweet-remaining
            aria-live="polite"
        ></span>
    </div>
    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
        {{ $maxLength }}文字まで
    </p>
</div>
