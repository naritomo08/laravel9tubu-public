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

    <script>
        (() => {
            const table = document.querySelector('[data-account-stats-table]');
            const body = document.querySelector('[data-account-stats-body]');

            if (!table || !body) {
                return;
            }

            const escapeHtml = (value) => {
                const div = document.createElement('div');
                div.textContent = value ?? '';
                return div.innerHTML;
            };

            const renderRow = (stats) => {
                body.innerHTML = `
                    <tr class="bg-blue-50 font-bold dark:bg-gray-800">
                        <td class="py-2 px-4 border-b dark:border-gray-700">${escapeHtml(stats.label)}</td>
                        <td class="py-2 px-4 border-b text-right dark:border-gray-700">${stats.tweet_count}</td>
                        <td class="py-2 px-4 border-b text-right dark:border-gray-700">${stats.like_count}</td>
                    </tr>
                `;
            };

            const refreshStats = async () => {
                try {
                    const response = await fetch(table.dataset.statsUrl, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                    });

                    if (!response.ok) {
                        return;
                    }

                    renderRow(await response.json());
                } catch (error) {
                    console.error('Error refreshing account stats:', error);
                }
            };

            window.setInterval(refreshStats, 15000);
        })();

        (() => {
            const table = document.querySelector('[data-account-scheduled-tweets-table]');
            const body = document.querySelector('[data-account-scheduled-tweets-body]');

            if (!table || !body) {
                return;
            }

            const refreshScheduledTweets = async () => {
                try {
                    const response = await fetch(table.dataset.scheduledTweetsUrl, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                    });

                    if (!response.ok) {
                        return;
                    }

                    const data = await response.json();
                    body.innerHTML = data.html ?? '';
                } catch (error) {
                    console.error('Error refreshing account scheduled tweets:', error);
                }
            };

            window.setInterval(refreshScheduledTweets, 15000);
        })();
    </script>
</x-layout>
