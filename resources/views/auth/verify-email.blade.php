<x-guest-layout>
    <x-auth-card>
        <x-slot name="logo">
            <a href="/">
                <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
            </a>
        </x-slot>

        @php($isPendingInitialEmailVerification = Auth::user()->isPendingInitialEmailVerification())

        <div class="mb-4 text-sm text-gray-600">
            @if($isPendingInitialEmailVerification)
                ユーザー登録ありがとうございます。登録時のメールアドレスに届いた認証メール内のリンクから、メール認証を完了してください。メールが届いていない場合は再送できます。
            @else
                メールアドレスの確認が完了していません。新しいメールアドレスに届いた認証メール内のリンクから、メール認証を完了してください。メールが届いていない場合は再送できます。
            @endif
        </div>

        @if($isPendingInitialEmailVerification)
            <div class="mb-4 text-sm font-bold text-red-600">
                ※登録から1時間以内にメール認証が完了しない場合、アカウントは自動的に削除されます。
            </div>
        @endif

        @if (session('status') == 'verification-link-sent')
            <div class="mb-4 font-medium text-sm text-green-600">
                @if($isPendingInitialEmailVerification)
                    登録時のメールアドレスに、新しい認証メールを送信しました。
                @else
                    新しいメールアドレスに、認証メールを再送しました。
                @endif
            </div>
        @endif

        <div class="mt-4 flex items-center justify-between">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf

                <div>
                    <x-button>
                        {{ __('Resend Verification Email') }}
                    </x-button>
                </div>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900">
                    {{ __('Log Out') }}
                </button>
            </form>
        </div>
    </x-auth-card>
</x-guest-layout>
