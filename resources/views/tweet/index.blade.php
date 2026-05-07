<x-layout :title="'TOP | ' . config('app.name', 'Laravel')">
    <x-layout.single>
        <h2 class="text-center text-blue-500 text-4xl font-bold mt-8 mb-8">
            {{ config('app.name', 'Laravel') }}
        </h2>
        <style>
            [data-email-verification-state="unverified"] [data-requires-verified-email] {
                display: none !important;
            }
        </style>
        @if(Auth::check())
            <div
                data-email-verification-watch
                data-status-url="{{ route('verification.status', [], false) }}"
                data-verified-url="{{ route('tweet.index', ['verified' => 1], false) }}"
                data-verification-send-url="{{ route('verification.send', [], false) }}"
                data-csrf-token="{{ csrf_token() }}"
                data-is-verified="{{ Auth::user()->hasVerifiedEmail() ? 'true' : 'false' }}"
            >
                @if(!Auth::user()->hasVerifiedEmail())
                    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4" data-email-verification-warning>
                        @if(Auth::user()->isPendingInitialEmailVerification())
                            メール認証が完了していません。登録時のメールアドレスに届いた認証メールをご確認ください。<br>
                            <span class="text-red-600 font-bold">※登録から1時間以内にメール認証が完了しない場合、アカウントは自動的に削除されます。</span>
                        @else
                            メール認証が完了していません。新しいメールアドレスに届いた認証メールをご確認ください。
                        @endif
                        <form method="POST" action="{{ route('verification.send') }}">
                            @csrf
                            <button type="submit" class="underline text-blue-600">認証メールを再送する</button>
                        </form>
                    </div>
                @endif
            </div>
        @endif
        @if(Auth::check())
            <div
                class="mb-4 text-right"
                data-admin-nav-watch
                data-status-url="{{ route('account.admin.status', [], false) }}"
                data-admin-url="{{ route('admin.users.index', [], false) }}"
                data-is-admin="{{ Auth::user()->is_admin ? 'true' : 'false' }}"
            >
                @if(Auth::user()->is_admin)
                    <x-element.button-a :href="route('admin.users.index')">
                        管理者画面
                    </x-element.button-a>
                @endif
            </div>
        @endif
        @if(Auth::check())
            <div data-admin-2fa-warning-watch>
                @if(Auth::user()->is_admin && !Auth::user()->hasEnabledTwoFactorAuthentication())
                    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4" data-admin-two-factor-warning>
                        管理者自身の2段階認証が未設定のため、他ユーザーのつぶやきの編集・削除はできません。2段階認証はアカウント設定から有効化できます。
                    </div>
                @endif
            </div>
        @endif
        @if (session('feedback.success'))
            <x-alert.success>{{ session('feedback.success') }}</x-alert.success>
        @endif
        <x-tweet.form.post :currentPage="$tweets->currentPage()"></x-tweet.form.post>
        <x-tweet.list :tweets="$tweets"></x-tweet.list>
    </x-layout.single>
</x-layout>
