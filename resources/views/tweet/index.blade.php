<x-layout title="TOP | つぶやきアプリ">
    <x-layout.single>
        <h2 class="text-center text-blue-500 text-4xl font-bold mt-8 mb-8">
            つぶやきアプリ
        </h2>
        @if(Auth::check() && !Auth::user()->hasVerifiedEmail())
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4">
                メール認証が完了していません。認証メールをご確認ください。<br>
                <span class="text-red-600 font-bold">※メール認証が1時間以内に完了しない場合、アカウントは自動的に削除されます。</span>
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <button type="submit" class="underline text-blue-600">認証メールを再送する</button>
                </form>
            </div>
        @endif
        @if(Auth::check() && Auth::user()->is_admin)
            <div class="mb-4 text-right">
                <a href="/admin/users" style="color: #2563eb !important; text-decoration: underline; font-weight: bold; background: none; display: inline-block;">管理者画面</a>
            </div>
        @endif
        <x-tweet.form.post></x-tweet.form.post>
        <x-tweet.list :tweets="$tweets"></x-tweet.list>
    </x-layout.single>
</x-layout>