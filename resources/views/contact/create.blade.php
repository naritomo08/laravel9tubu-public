<x-layout title="お問い合わせ | つぶやきアプリ">
    <x-layout.single>
        <h2 class="text-center text-blue-700 text-3xl font-bold mt-8 mb-8">
            お問い合わせ
        </h2>

        <div class="mb-6">
            <x-element.button-a :href="route('tweet.index')">
                トップに戻る
            </x-element.button-a>
        </div>

        @if (session('feedback.success'))
            <x-alert.success>{{ session('feedback.success') }}</x-alert.success>
        @endif

        <x-auth-validation-errors class="mb-4" :errors="$errors" />

        <div class="bg-white border border-gray-200 p-6 mb-8 dark:border-gray-800 dark:bg-gray-900">
            <form method="POST" action="{{ route('contact.store') }}">
                @csrf

                <div>
                    <x-label value="ユーザー名" />
                    <p class="mt-1 rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                        {{ Auth::user()->name }}
                    </p>
                </div>

                <div class="mt-4">
                    <x-label value="メールアドレス" />
                    <p class="mt-1 rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                        {{ Auth::user()->email }}
                    </p>
                </div>

                <div class="mt-4">
                    <x-label for="body" value="問い合わせ内容" />
                    <textarea
                        id="body"
                        name="body"
                        rows="8"
                        required
                        class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                    >{{ old('body') }}</textarea>
                </div>

                <div class="flex justify-end mt-6">
                    <x-button>
                        送信する
                    </x-button>
                </div>
            </form>
        </div>
    </x-layout.single>
</x-layout>
