@extends('layouts.guest')

@section('content')
<div class="min-h-screen flex flex-col items-center justify-center bg-gray-100">
    <div class="bg-white p-8 rounded shadow-md w-full max-w-md">
        <h1 class="text-2xl font-bold text-red-600 mb-4">画像容量オーバー</h1>
        <div class="mb-4 text-gray-700">
            アップロードできる画像の合計サイズを超えています。<br>
            画像の容量を減らして再度お試しください。
        </div>
        <a href="/" class="inline-block bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">トップページに戻る</a>
    </div>
</div>
@endsection
