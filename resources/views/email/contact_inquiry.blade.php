@component('mail::message')

# 問い合わせが届きました

@component('mail::panel')
ユーザー名: {{ $name }}

メールアドレス: {{ $email }}
@endcomponent

## 問い合わせ内容

{{ $body }}

@endcomponent
