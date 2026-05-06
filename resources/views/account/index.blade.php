<x-layout :title="'アカウント設定 | ' . config('app.name', 'Laravel')">
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
        <x-auth-validation-errors class="mb-4" :errors="$errors->confirmTwoFactorAuthentication" />

        @php
            $user = Auth::user();
            $twoFactorEnabled = $user->hasEnabledTwoFactorAuthentication();
            $twoFactorPending = filled($user->two_factor_secret) && ! $twoFactorEnabled;
            $twoFactorStatusMessages = [
                Laravel\Fortify\Fortify::TWO_FACTOR_AUTHENTICATION_ENABLED => '認証アプリでQRコードを読み取り、認証コードを入力して2段階認証を有効化してください。',
                Laravel\Fortify\Fortify::TWO_FACTOR_AUTHENTICATION_CONFIRMED => '2段階認証を有効化しました。',
                Laravel\Fortify\Fortify::TWO_FACTOR_AUTHENTICATION_DISABLED => '2段階認証を無効化しました。',
                Laravel\Fortify\Fortify::RECOVERY_CODES_GENERATED => 'リカバリーコードを再生成しました。',
            ];
        @endphp

        @if (session('status') && isset($twoFactorStatusMessages[session('status')]))
            <x-alert.success>{{ $twoFactorStatusMessages[session('status')] }}</x-alert.success>
        @endif

        <div class="bg-white border border-gray-200 p-6 mb-8 dark:border-gray-800 dark:bg-gray-900">
            <h3 class="text-xl font-bold mb-4">あなたのつぶやき・いいね集計</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200 dark:bg-gray-900 dark:border-gray-700" data-account-stats-table data-stats-url="{{ route('account.stats', [], false) }}">
                    <thead>
                        <tr>
                            <th class="py-2 px-4 border-b text-left dark:border-gray-700">対象</th>
                            <th class="py-2 px-4 border-b text-right dark:border-gray-700">つぶやき数</th>
                            <th class="py-2 px-4 border-b text-right dark:border-gray-700">いいね数</th>
                        </tr>
                    </thead>
                    <tbody data-account-stats-body>
                        <tr class="bg-blue-50 font-bold dark:bg-gray-800">
                            <td class="py-2 px-4 border-b dark:border-gray-700">{{ $stats['label'] }}</td>
                            <td class="py-2 px-4 border-b text-right dark:border-gray-700">{{ $stats['tweet_count'] }}</td>
                            <td class="py-2 px-4 border-b text-right dark:border-gray-700">{{ $stats['like_count'] }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="text-sm text-gray-500 mt-2 dark:text-gray-400">自分の投稿数と、自分の投稿に付いたいいね総数を自動更新します。</p>
        </div>

        <div class="bg-white border border-gray-200 p-6 mb-8 dark:border-gray-800 dark:bg-gray-900">
            <h3 class="text-xl font-bold mb-4">あなたの予約投稿一覧</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200 dark:bg-gray-900 dark:border-gray-700" data-account-scheduled-tweets-table data-scheduled-tweets-url="{{ route('account.scheduled-tweets', [], false) }}">
                    <thead>
                        <tr>
                            <th class="py-2 px-4 border-b text-left dark:border-gray-700">内容</th>
                            <th class="py-2 px-4 border-b text-left dark:border-gray-700">予約日時</th>
                            <th class="py-2 px-4 border-b text-center dark:border-gray-700">編集</th>
                            <th class="py-2 px-4 border-b text-center dark:border-gray-700">削除</th>
                        </tr>
                    </thead>
                    <tbody data-account-scheduled-tweets-body>
                        @include('account._scheduled_tweets', ['scheduledTweets' => $scheduledTweets])
                    </tbody>
                </table>
            </div>
            <p class="text-sm text-gray-500 mt-2 dark:text-gray-400">予約時刻を過ぎた投稿は自動更新で一覧から外れます。</p>
        </div>

        <div class="bg-white border border-gray-200 p-6 mb-8 dark:border-gray-800 dark:bg-gray-900">
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

                <p class="text-sm text-gray-700 mt-4 dark:text-gray-300">
                    メールアドレスを変更した場合は、新しいメールアドレスの確認が必要です。
                </p>

                <div class="flex justify-end mt-6">
                    <x-button>
                        変更する
                    </x-button>
                </div>
            </form>
        </div>

        <div class="bg-white border border-gray-200 p-6 mb-8 dark:border-gray-800 dark:bg-gray-900">
            <h3 class="text-xl font-bold mb-4">メール通知設定</h3>

            <form method="POST" action="{{ route('account.mail-settings.update') }}">
                @csrf
                @method('PUT')

                <label for="receives_notification_mail" class="flex items-start gap-3 text-sm text-gray-700 dark:text-gray-300">
                    <input
                        id="receives_notification_mail"
                        class="mt-1 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                        type="checkbox"
                        name="receives_notification_mail"
                        value="1"
                        @checked(old('receives_notification_mail', Auth::user()->receives_notification_mail))
                    >
                    <span>
                        通知メールとつぶやき数・いいね数の日次集計メールを受け取る
                    </span>
                </label>

                <p class="text-sm text-gray-700 mt-4 dark:text-gray-300">
                    メール認証やパスワード再設定など、アカウント利用に必要なメールは引き続き送信されます。
                </p>

                <div class="flex justify-end mt-6">
                    <x-button>
                        変更する
                    </x-button>
                </div>
            </form>
        </div>

        <div class="bg-white border border-gray-200 p-6 mb-8 dark:border-gray-800 dark:bg-gray-900">
            <h3 class="text-xl font-bold mb-4">Google連携</h3>

            @if (Auth::user()->google_id)
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    Googleアカウント連携済みです。
                    @if (Auth::user()->google_email)
                        連携中: {{ Auth::user()->google_email }}
                    @endif
                </p>

                <div class="flex justify-end mt-6">
                    <form method="POST" action="{{ route('account.google.disconnect') }}">
                        @csrf
                        @method('DELETE')

                        <x-button type="submit">
                            連携を解除する
                        </x-button>
                    </form>
                </div>
            @else
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    通常ログイン済みの状態でGoogleアカウントを紐付けると、次回からGoogle認証でもログインできます。
                </p>

                <div class="flex justify-end mt-6">
                    <a
                        href="{{ route('account.google.connect') }}"
                        class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                    >
                        Googleアカウントを連携する
                    </a>
                </div>
            @endif
        </div>

        <div class="bg-white border border-gray-200 p-6 mb-8 dark:border-gray-800 dark:bg-gray-900">
            <h3 class="text-xl font-bold mb-4">2段階認証</h3>

            @if ($twoFactorEnabled)
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    2段階認証は有効です。ログイン時に認証アプリのコード、またはリカバリーコードが必要になります。
                </p>

                @if ($user->two_factor_recovery_codes)
                    <div class="mt-4">
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">リカバリーコード</p>
                        <div class="mt-2 grid gap-2 sm:grid-cols-2">
                            @foreach ($user->recoveryCodes() as $code)
                                <code class="block border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">{{ $code }}</code>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="mt-6 flex flex-wrap justify-end gap-3">
                    <form method="POST" action="{{ route('two-factor.regenerate-recovery-codes') }}">
                        @csrf

                        <x-button type="submit">
                            リカバリーコードを再生成する
                        </x-button>
                    </form>

                    <form method="POST" action="{{ route('two-factor.disable') }}">
                        @csrf
                        @method('DELETE')

                        <x-button type="submit">
                            無効化する
                        </x-button>
                    </form>
                </div>
            @elseif ($twoFactorPending)
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    認証アプリでQRコードを読み取り、表示された6桁のコードを入力してください。
                </p>

                <div class="mt-4 inline-block border border-gray-200 bg-white p-4 dark:border-gray-700">
                    {!! $user->twoFactorQrCodeSvg() !!}
                </div>

                @if ($user->two_factor_recovery_codes)
                    <div class="mt-4">
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">リカバリーコード</p>
                        <div class="mt-2 grid gap-2 sm:grid-cols-2">
                            @foreach ($user->recoveryCodes() as $code)
                                <code class="block border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">{{ $code }}</code>
                            @endforeach
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('two-factor.confirm') }}" class="mt-6">
                    @csrf

                    <div>
                        <x-label for="two_factor_code" value="認証コード" />
                        <x-input id="two_factor_code" class="block mt-1 w-full" type="text" inputmode="numeric" name="code" required autocomplete="one-time-code" />
                    </div>

                    <div class="mt-6 flex flex-wrap justify-end gap-3">
                        <x-button type="submit">
                            有効化を完了する
                        </x-button>
                    </div>
                </form>

                <form method="POST" action="{{ route('two-factor.disable') }}" class="mt-3 flex justify-end">
                    @csrf
                    @method('DELETE')

                    <x-button type="submit">
                        設定を取り消す
                    </x-button>
                </form>
            @else
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    認証アプリを使った2段階認証を追加できます。有効化操作では、先に現在のパスワード確認が求められます。
                </p>

                <div class="flex justify-end mt-6">
                    <form method="POST" action="{{ route('two-factor.enable') }}">
                        @csrf

                        <x-button type="submit">
                            有効化する
                        </x-button>
                    </form>
                </div>
            @endif
        </div>

        <div class="bg-white border border-gray-200 p-6 mb-8 dark:border-gray-800 dark:bg-gray-900">
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
            <div class="bg-white border border-red-200 p-6 mb-8 dark:border-red-900 dark:bg-gray-900">
                <h3 class="text-xl font-bold text-red-700 mb-4">アカウント削除</h3>
                <p class="text-sm text-gray-700 mb-4 dark:text-gray-300">
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
