@php
    $canManageUsers = Auth::user()?->hasEnabledTwoFactorAuthentication();
@endphp

@foreach($users as $user)
    <tr>
        <td class="py-2 px-4 border-b dark:border-gray-700">{{ $user->name }}</td>
        <td class="py-2 px-4 border-b dark:border-gray-700">
            {{ $user->email }}
            @if($canManageUsers && Auth::id() !== $user->id && ! $user->is_seed_admin)
                <form method="POST" action="{{ route('admin.users.email.update', $user->id) }}" class="mt-2 flex flex-wrap items-center gap-2" onsubmit="return confirm('このユーザーのメールアドレスを変更しますか？');">
                    @csrf
                    @method('PUT')
                    <input
                        type="email"
                        name="email"
                        value="{{ $user->email }}"
                        required
                        data-live-refresh-edit-guard
                        class="w-56 rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                    >
                    <x-element.button theme="secondary">
                        メール変更
                    </x-element.button>
                </form>
            @endif
        </td>
        <td class="py-2 px-4 border-b text-center dark:border-gray-700">
            @if($user->is_admin)
                <span class="text-green-600 font-bold">✔</span>
                @if($user->is_seed_admin)
                    <span class="ml-1 text-xs text-gray-500">固定</span>
                @endif
            @endif
        </td>
        <td class="py-2 px-4 border-b text-center dark:border-gray-700">
            @if($user->email_verified_at)
                <span class="text-green-600 font-bold">✔</span>
            @endif
        </td>
        <td class="py-2 px-4 border-b text-center dark:border-gray-700">
            @if($user->receives_notification_mail)
                <span class="text-green-600 font-bold">✔</span>
            @else
                <span class="text-gray-400">停止</span>
            @endif
        </td>
        <td class="py-2 px-4 border-b text-center dark:border-gray-700">
            @if($user->google_id)
                <span class="text-green-600 font-bold">✔</span>
            @endif
        </td>
        <td class="py-2 px-4 border-b text-center dark:border-gray-700">
            @if($user->hasEnabledTwoFactorAuthentication())
                <span class="text-green-600 font-bold">✔</span>
            @endif
        </td>
        <td class="py-2 px-4 border-b text-center dark:border-gray-700">
            @if($canManageUsers && Auth::id() !== $user->id)
                @if($user->hasEnabledTwoFactorAuthentication() && ! $user->is_seed_admin)
                    <form method="POST" action="{{ route('admin.users.two-factor.reset', $user->id) }}" class="inline-block" onsubmit="return confirm('このユーザーの2段階認証をリセットしますか？');">
                        @csrf
                        @method('PUT')
                        <x-element.button theme="secondary">
                            2FAリセット
                        </x-element.button>
                    </form>
                @endif
                @if($user->is_admin)
                    @if(!$user->is_seed_admin)
                        <form method="POST" action="{{ route('admin.users.admin.update', $user->id) }}" class="inline-block" onsubmit="return confirm('管理者から外しますか？');">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="is_admin" value="0">
                            <x-element.button theme="secondary">
                                管理者から外す
                            </x-element.button>
                        </form>
                    @endif
                @else
                    <form method="POST" action="{{ route('admin.users.admin.update', $user->id) }}" class="inline-block" onsubmit="return confirm('管理者にしますか？');">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="is_admin" value="1">
                        <x-element.button>
                            管理者にする
                        </x-element.button>
                    </form>
                @endif
            @endif
            @if($canManageUsers && !$user->is_admin)
                <form method="POST" action="{{ route('admin.users.destroy', $user->id) }}" class="inline-block" onsubmit="return confirm('本当に削除しますか？');">
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
