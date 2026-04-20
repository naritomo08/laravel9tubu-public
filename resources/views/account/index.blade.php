<x-layout title="アカウント設定 | つぶやきアプリ">
    <x-layout.single>
        <h2 class="text-center text-blue-700 text-3xl font-bold mt-8 mb-8">
            アカウント設定
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

        <div class="bg-white border border-gray-200 p-6 mb-8">
            <h3 class="text-xl font-bold mb-4">プロフィール変更</h3>

            <form method="POST" action="{{ route('account.profile.update') }}">
                @csrf
                @method('PUT')

                <div>
                    <x-label for="name" value="ユーザー名" />
                    <x-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', Auth::user()->name)" required autocomplete="name" />
                </div>

                <div class="mt-4">
                    <x-label for="email" value="メールアドレス" />
                    <x-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', Auth::user()->email)" required autocomplete="email" />
                </div>

                <p class="text-sm text-gray-700 mt-4">
                    メールアドレスを変更した場合は、新しいメールアドレスの確認が必要です。
                </p>

                <div class="flex justify-end mt-6">
                    <x-button>
                        変更する
                    </x-button>
                </div>
            </form>
        </div>

        <div class="bg-white border border-gray-200 p-6 mb-8">
            <h3 class="text-xl font-bold mb-4">パスワード変更</h3>

            <form method="POST" action="{{ route('account.password.update') }}">
                @csrf
                @method('PUT')

                <div>
                    <x-label for="current_password" value="現在のパスワード" />
                    <x-input id="current_password" class="block mt-1 w-full" type="password" name="current_password" required autocomplete="current-password" />
                </div>

                <div class="mt-4">
                    <x-label for="password" value="新しいパスワード" />
                    <x-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
                </div>

                <div class="mt-4">
                    <x-label for="password_confirmation" value="新しいパスワード（確認）" />
                    <x-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
                </div>

                <div class="flex justify-end mt-6">
                    <x-button>
                        変更する
                    </x-button>
                </div>
            </form>
        </div>

        @if(!Auth::user()->is_admin)
            <div class="bg-white border border-red-200 p-6 mb-8">
                <h3 class="text-xl font-bold text-red-700 mb-4">アカウント削除</h3>
                <p class="text-sm text-gray-700 mb-4">
                    アカウントを削除すると、投稿といいねも削除されます。削除後はログアウトしてトップに戻ります。
                </p>

                <form method="POST" action="{{ route('account.destroy') }}" onsubmit="return confirm('本当にアカウントを削除しますか？');">
                    @csrf
                    @method('DELETE')

                    <div>
                        <x-label for="delete_current_password" value="現在のパスワード" />
                        <x-input id="delete_current_password" class="block mt-1 w-full" type="password" name="current_password" required autocomplete="current-password" />
                    </div>

                    <div class="flex justify-end mt-6">
                        <x-button style="background-color: #dc2626; color: #ffffff;">
                            削除する
                        </x-button>
                    </div>
                </form>
            </div>
        @endif
    </x-layout.single>
</x-layout>
