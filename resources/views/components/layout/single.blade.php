<div class="flex justify-center">
    <div class="max-w-screen-sm w-full">
        @auth
            <div class="flex justify-end items-center gap-3 p-4">
                @if(Auth::user()->hasVerifiedEmail())
                    <x-element.button-a :href="route('account.index')">
                        アカウント設定
                    </x-element.button-a>
                @endif
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <x-element.button theme="secondary">
                        ログアウト
                    </x-element.button>
                </form>
            </div>
            <div class="px-4 py-2 text-lg font-semibold text-blue-700">
                ようこそ{{ Auth::user()->name }}さん
            </div>
        @endauth
        {{ $slot }}
    </div>
</div>
