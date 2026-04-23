<x-layout title="ユーザー管理 | 管理者画面">
    <x-layout.single>
        <h2 class="text-center text-blue-700 text-3xl font-bold mt-8 mb-8">
            管理者画面
        </h2>
        <div class="mb-4">
            <x-element.button-a :href="route('tweet.index')">
                トップに戻る
            </x-element.button-a>
        </div>
        @if (session('success'))
            <x-alert.success>{{ session('success') }}</x-alert.success>
        @endif
        @if (session('error'))
            <x-alert.error>{{ session('error') }}</x-alert.error>
        @endif
        <x-auth-validation-errors class="mb-4" :errors="$errors" />

        <section class="mb-8">
            <h3 class="text-xl font-bold text-gray-800 mb-3">つぶやき・いいね集計</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200" data-admin-stats-table data-stats-url="{{ route('admin.users.stats') }}">
                    <thead>
                        <tr>
                            <th class="py-2 px-4 border-b text-left">対象</th>
                            <th class="py-2 px-4 border-b text-right">つぶやき数</th>
                            <th class="py-2 px-4 border-b text-right">いいね数</th>
                        </tr>
                    </thead>
                    <tbody data-admin-stats-body>
                        <tr class="bg-blue-50 font-bold">
                            <td class="py-2 px-4 border-b">{{ $stats['totals']['label'] }}</td>
                            <td class="py-2 px-4 border-b text-right">{{ $stats['totals']['tweet_count'] }}</td>
                            <td class="py-2 px-4 border-b text-right">{{ $stats['totals']['like_count'] }}</td>
                        </tr>
                        @foreach($stats['users'] as $statUser)
                            <tr>
                                <td class="py-2 px-4 border-b">{{ $statUser['name'] }}</td>
                                <td class="py-2 px-4 border-b text-right">{{ $statUser['tweet_count'] }}</td>
                                <td class="py-2 px-4 border-b text-right">{{ $statUser['like_count'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="text-sm text-gray-500 mt-2">投稿数と、そのユーザーの投稿に付いたいいね総数を自動更新します。</p>
        </section>

        <section>
            <h3 class="text-xl font-bold text-gray-800 mb-3">ユーザー一覧</h3>
            <table class="min-w-full bg-white border border-gray-200">
                <thead>
                    <tr>
                        <th class="py-2 px-4 border-b">名前</th>
                        <th class="py-2 px-4 border-b">メール</th>
                        <th class="py-2 px-4 border-b">管理者</th>
                        <th class="py-2 px-4 border-b">メール認証</th>
                        <th class="py-2 px-4 border-b">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                        <tr>
                            <td class="py-2 px-4 border-b">{{ $user->name }}</td>
                            <td class="py-2 px-4 border-b">
                                <form method="POST" action="{{ route('admin.users.email.update', $user->id) }}" class="flex items-center gap-2">
                                    @csrf
                                    @method('PUT')
                                    <x-input class="block w-full" type="email" name="email" value="{{ $user->email }}" required />
                                    <x-element.button>
                                        変更
                                    </x-element.button>
                                </form>
                            </td>
                            <td class="py-2 px-4 border-b text-center">
                                @if($user->is_admin)
                                    <span class="text-green-600 font-bold">✔</span>
                                @endif
                            </td>
                            <td class="py-2 px-4 border-b text-center">
                                @if($user->email_verified_at)
                                    <span class="text-green-600 font-bold">✔</span>
                                @endif
                            </td>
                            <td class="py-2 px-4 border-b text-center">
                                @if(!$user->is_admin)
                                    <form method="POST" action="{{ route('admin.users.destroy', $user->id) }}" style="display:inline;" onsubmit="return confirm('本当に削除しますか？');">
                                        @csrf
                                        @method('DELETE')
                                        <x-element.button theme="secondary">
                                            削除
                                        </x-element.button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    </x-layout.single>

    <script>
        (() => {
            const table = document.querySelector('[data-admin-stats-table]');
            const body = document.querySelector('[data-admin-stats-body]');

            if (!table || !body) {
                return;
            }

            const escapeHtml = (value) => {
                const div = document.createElement('div');
                div.textContent = value ?? '';
                return div.innerHTML;
            };

            const renderRows = (stats) => {
                const totalRow = `
                    <tr class="bg-blue-50 font-bold">
                        <td class="py-2 px-4 border-b">${escapeHtml(stats.totals.label)}</td>
                        <td class="py-2 px-4 border-b text-right">${stats.totals.tweet_count}</td>
                        <td class="py-2 px-4 border-b text-right">${stats.totals.like_count}</td>
                    </tr>
                `;

                const userRows = stats.users.map((user) => `
                    <tr>
                        <td class="py-2 px-4 border-b">${escapeHtml(user.name)}</td>
                        <td class="py-2 px-4 border-b text-right">${user.tweet_count}</td>
                        <td class="py-2 px-4 border-b text-right">${user.like_count}</td>
                    </tr>
                `).join('');

                body.innerHTML = totalRow + userRows;
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

                    renderRows(await response.json());
                } catch (error) {
                    console.error('Error refreshing admin stats:', error);
                }
            };

            window.setInterval(refreshStats, 15000);
        })();
    </script>
</x-layout>
