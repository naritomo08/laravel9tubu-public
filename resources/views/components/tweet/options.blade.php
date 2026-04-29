@php($pageParameter = $currentPage ? ['page' => $currentPage] : [])
@php($returnUrlParameter = $returnUrl ? ['return_url' => $returnUrl] : [])
@php($navigationParameters = array_merge($pageParameter, $returnUrlParameter))

@if(Auth::check())
    @if($canEditTweet || $canDeleteTweet || $canManageProtection)
        <details class="tweet-option relative text-gray-500">
            <summary>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                </svg>
            </summary>
            <div class="bg-white rounded shadow-md absolute right-0 w-28 z-20 pt-1 pb-1 dark:bg-gray-800">
                @if($canManageProtection)
                    <div>
                        <form action="{{ route('tweet.protection', ['tweet' => $tweetId]) }}" method="post" onclick="return confirm('{{ $isProtected ? '保護を解除しますか?' : '保護しますか?' }}');">
                            @method('PUT')
                            @csrf
                            <input type="hidden" name="is_protected" value="{{ $isProtected ? 0 : 1 }}">
                            <button type="submit" class="flex items-center w-full pt-1 pb-1 pl-3 pr-3 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 1.944a1 1 0 01.757.346 12.7 12.7 0 005.158 3.39 1 1 0 01.686.949c0 4.616-2.264 8.882-6.16 11.226a1 1 0 01-1.032 0C5.514 15.511 3.25 11.245 3.25 6.629a1 1 0 01.686-.949 12.7 12.7 0 005.158-3.39A1 1 0 0110 1.944z" clip-rule="evenodd" />
                                </svg>
                                <span>{{ $isProtected ? '保護解除' : '保護' }}</span>
                            </button>
                        </form>
                    </div>
                @endif

                @if($canEditTweet)
                    <div>
                        <a href="{{ route('tweet.update.index', array_merge(['tweetId' => $tweetId], $navigationParameters)) }}" class="flex items-center pt-1 pb-1 pl-3 pr-3 hover:bg-gray-100 dark:hover:bg-gray-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" />
                                <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd" />
                            </svg>
                            <span>編集</span>
                        </a>
                    </div>
                @endif

                @if($canDeleteTweet)
                    <div>
                        <form action="{{ route('tweet.delete', ['tweetId' => $tweetId]) }}" method="post" onclick="return confirm('削除してもよろしいですか?');">
                            @method('DELETE')
                            @csrf
                            @if($currentPage)
                                <input type="hidden" name="page" value="{{ $currentPage }}">
                            @endif
                            @if($returnUrl)
                                <input type="hidden" name="return_url" value="{{ $returnUrl }}">
                            @endif
                            <button type="submit" class="flex items-center w-full pt-1 pb-1 pl-3 pr-3 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                <span>削除</span>
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </details>
    @endif
@endif

@once
@push('css')
    <style>
        .tweet-option > summary {
            list-style: none;
            cursor: pointer;
        }
        .tweet-option[open] > summary::before {
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            z-index: 10;
            display: block;
            content: " ";
            background: transparent;
        }
    </style>
@endpush
@endonce
