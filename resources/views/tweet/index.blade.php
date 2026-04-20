<x-layout title="TOP | つぶやきアプリ">
    <x-layout.single>
        <h2 class="text-center text-blue-500 text-4xl font-bold mt-8 mb-8">
            つぶやきアプリ
        </h2>
        @if(Auth::check() && !Auth::user()->hasVerifiedEmail())
            <div
                class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4"
                data-email-verification-watch
                data-status-url="{{ route('verification.status') }}"
                data-verified-url="{{ route('tweet.index', ['verified' => 1]) }}"
            >
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
                <x-element.button-a :href="route('admin.users.index')">
                    管理者画面
                </x-element.button-a>
            </div>
        @endif
        @if (session('feedback.success'))
            <x-alert.success>{{ session('feedback.success') }}</x-alert.success>
        @endif
        <x-tweet.form.post :currentPage="$tweets->currentPage()"></x-tweet.form.post>
        <x-tweet.list :tweets="$tweets"></x-tweet.list>
    </x-layout.single>
</x-layout>
