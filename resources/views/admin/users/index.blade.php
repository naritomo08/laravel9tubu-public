<x-layout title="ユーザー管理 | 管理者画面">
    <x-layout.single>
        <h2 class="text-center text-blue-700 text-3xl font-bold mt-8 mb-8">
            ユーザー一覧
        </h2>
            <div style="margin-bottom: 1em;">
                <a href="/" style="text-decoration: underline; color: #3490dc;">トップに戻る</a>
            </div>
        <table class="min-w-full bg-white border border-gray-200">
            <thead>
                <tr>
                    <th class="py-2 px-4 border-b">名前</th>
                    <th class="py-2 px-4 border-b">メール</th>
                    <th class="py-2 px-4 border-b">管理者</th>
                    <th class="py-2 px-4 border-b">操作</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                    <tr>
                        <td class="py-2 px-4 border-b">{{ $user->name }}</td>
                        <td class="py-2 px-4 border-b">{{ $user->email }}</td>
                        <td class="py-2 px-4 border-b text-center">
                            @if($user->is_admin)
                                <span class="text-green-600 font-bold">✔</span>
                            @endif
                        </td>
                        <td class="py-2 px-4 border-b text-center">
                            @if(!$user->is_admin)
                                <form method="POST" action="{{ route('admin.users.destroy', $user->id) }}" style="display:inline;" onsubmit="return confirm('本当に削除しますか？');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" style="background:none;border:none;padding:0;color:#dc2626 !important;text-decoration:underline;cursor:pointer;font-weight:bold;display:inline-block;">削除</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-layout.single>
</x-layout>
