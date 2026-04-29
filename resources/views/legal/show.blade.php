<x-layout title="{{ $title }} | つぶやきアプリ">
    <x-layout.single>
        <article class="px-4 pb-12">
            <div class="mb-6">
                <x-element.button-a :href="route('tweet.index')">
                    トップに戻る
                </x-element.button-a>
            </div>

            <div class="bg-white border border-gray-200 p-6 dark:border-gray-800 dark:bg-gray-900">
                <div class="markdown-body">
                    {!! $content !!}
                </div>
            </div>
        </article>
    </x-layout.single>
</x-layout>
