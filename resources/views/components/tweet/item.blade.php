@props([
    'tweet',
    'currentPage' => null,
    'returnUrl' => null,
])
@php($tweetVersion = $tweet->version())

<li class="border-b last:border-b-0 border-gray-200 p-4 flex items-start gap-3 dark:border-gray-800" data-tweet-id="{{ $tweet->id }}" data-tweet-created-at="{{ $tweet->created_at->toJSON() }}" data-tweet-version="{{ $tweetVersion }}">
    <div class="min-w-0 flex-1" style="min-width: 0; flex: 1 1 0%;">
        <div class="mb-2 flex flex-wrap items-center gap-2">
            <span class="inline-block rounded-full text-gray-600 bg-gray-100 px-2 py-1 text-xs dark:bg-gray-800 dark:text-gray-300">
                {{ $tweet->user->name }}
            </span>
            @if($tweet->is_secret)
                <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:ring-amber-800" title="シークレットモード">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v2H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-1V6a4 4 0 00-4-4zm2 6V6a2 2 0 10-4 0v2h4z" clip-rule="evenodd" />
                    </svg>
                    シークレット
                </span>
            @endif
            @if($tweet->is_protected)
                <span class="inline-flex items-center gap-1 rounded-full bg-sky-50 px-2 py-1 text-xs font-semibold text-sky-700 ring-1 ring-sky-200 dark:bg-sky-950/40 dark:text-sky-300 dark:ring-sky-800" title="保護">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 1.944a1 1 0 01.757.346 12.7 12.7 0 005.158 3.39 1 1 0 01.686.949c0 4.616-2.264 8.882-6.16 11.226a1 1 0 01-1.032 0C5.514 15.511 3.25 11.245 3.25 6.629a1 1 0 01.686-.949 12.7 12.7 0 005.158-3.39A1 1 0 0110 1.944z" clip-rule="evenodd" />
                    </svg>
                    保護
                </span>
            @endif
        </div>
        <p class="text-gray-600 break-all dark:text-gray-200" style="overflow-wrap: anywhere; word-break: break-word;">{!! $tweet->formatted_content !!}</p>
        <p class="text-gray-600 mt-1 dark:text-gray-400">{{ $tweet->created_at }}</p>
        <x-tweet.images :images="$tweet->images"/>
    </div>
    <div class="shrink-0 flex items-center gap-2">
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
        <x-tweet.options :tweetId="$tweet->id" :userId="$tweet->user_id" :currentPage="$currentPage" :returnUrl="$returnUrl" :isSeeded="$tweet->is_seeded" :isProtected="$tweet->is_protected" :tweetUserIsSeedAdmin="$tweet->user->is_seed_admin">
        </x-tweet.options>
    </div>
</li>
