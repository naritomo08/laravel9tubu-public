@foreach($users as $user)
    <tr>
        <td class="py-2 px-4 border-b dark:border-gray-700">{{ $user->name }}</td>
        <td class="py-2 px-4 border-b dark:border-gray-700">
            @if($user->is_seed_admin && Auth::id() !== $user->id)
                <span>{{ $user->email }}</span>
                <span class="ml-1 text-xs text-gray-500">固定</span>
            @else
                <form method="POST" action="{{ route('admin.users.email.update', $user->id) }}" class="flex items-center gap-2">
                    @csrf
                    @method('PUT')
                    <x-input class="block w-full" type="email" name="email" value="{{ $user->email }}" required />
                    <x-element.button>
                        変更
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
            @if($user->google_id)
                <span class="text-green-600 font-bold">✔</span>
            @endif
        </td>
        <td class="py-2 px-4 border-b text-center dark:border-gray-700">
            @if(Auth::id() !== $user->id)
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
            @if(!$user->is_admin)
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
