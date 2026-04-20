@props([
    'tweet',
    'currentPage' => null,
])
@php($tweetVersion = $tweet->user?->updated_at?->gt($tweet->updated_at) ? $tweet->user->updated_at : $tweet->updated_at)

<li class="border-b last:border-b-0 border-gray-200 p-4 flex items-start justify-between" data-tweet-id="{{ $tweet->id }}" data-tweet-updated-at="{{ $tweet->updated_at->toJSON() }}" data-tweet-version="{{ $tweetVersion->toJSON() }}">
    <div>
        <span class="inline-block rounded-full text-gray-600 bg-gray-100 px-2 py-1 text-xs mb-2">
            {{ $tweet->user->name }}
        </span>
        <p class="text-gray-600">{!! nl2br(e($tweet->content)) !!}</br>
        {!! nl2br(e($tweet->updated_at)) !!}</p>
        <x-tweet.images :images="$tweet->images"/>
    </div>
    <div class="flex items-center gap-2">
        <!-- いいねボタン -->
        @if(Auth::check() && Auth::user()->hasVerifiedEmail())
            <button 
                class="like-btn flex items-center gap-1 text-sm px-2 py-1 rounded transition-colors {{ $tweet->is_liked ? 'text-red-500 hover:text-red-600' : 'text-gray-400 hover:text-red-500' }}"
                data-tweet-id="{{ $tweet->id }}"
                data-is-liked="{{ $tweet->is_liked ? 'true' : 'false' }}"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="{{ $tweet->is_liked ? 'currentColor' : 'none' }}" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
                <span class="like-count">{{ $tweet->like_count ?? 0 }}</span>
            </button>
        @else
            <div class="flex items-center gap-1 text-sm text-gray-400 px-2 py-1" title="メール認証後にいいねできます">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
                <span class="like-count">{{ $tweet->like_count ?? 0 }}</span>
            </div>
        @endif
        <!-- 編集と削除 -->
        <x-tweet.options :tweetId="$tweet->id" :userId="$tweet->user_id" :currentPage="$currentPage">
        </x-tweet.options>
    </div>
</li>
