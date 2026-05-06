<x-guest-layout>
    <x-auth-card>
        <x-slot name="logo">
            <a href="/">
                <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
            </a>
        </x-slot>

        <div class="mb-4 text-sm text-gray-600 dark:text-gray-300">
            認証アプリに表示されている6桁のコード、またはリカバリーコードを入力してください。
        </div>

        <x-auth-validation-errors class="mb-4" :errors="$errors" />

        <form method="POST" action="{{ route('two-factor.login') }}">
            @csrf

            <div>
                <x-label for="code" value="認証コード" />
                <x-input id="code" class="block mt-1 w-full" type="text" inputmode="numeric" name="code" autofocus autocomplete="one-time-code" />
            </div>

            <div class="mt-4">
                <x-label for="recovery_code" value="リカバリーコード" />
                <x-input id="recovery_code" class="block mt-1 w-full" type="text" name="recovery_code" autocomplete="one-time-code" />
            </div>

            <div class="flex justify-end mt-4">
                <x-button>
                    ログイン
                </x-button>
            </div>
        </form>
    </x-auth-card>
</x-guest-layout>
