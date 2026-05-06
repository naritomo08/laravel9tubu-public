@forelse($scheduledTweets as $tweet)
    @php($canManageScheduledTweets = $canManageScheduledTweets ?? false)
    <tr>
        <td class="py-2 px-4 border-b dark:border-gray-700">{{ $tweet->user->name }}</td>
        <td class="py-2 px-4 border-b dark:border-gray-700">{{ $tweet->content }}</td>
        <td class="py-2 px-4 border-b whitespace-nowrap dark:border-gray-700">{{ $tweet->scheduled_at }}</td>
        <td class="py-2 px-4 border-b text-center dark:border-gray-700">
            @if($canManageScheduledTweets)
                <form action="{{ route('tweet.delete', ['tweetId' => $tweet->id]) }}" method="post" onclick="return confirm('削除してもよろしいですか?');">
                    @method('DELETE')
                    @csrf
                    <input type="hidden" name="return_url" value="{{ route('admin.users.index', [], false) }}">
                    <button type="submit" class="text-red-600 underline hover:text-red-700">
                        削除
                    </button>
                </form>
            @endif
        </td>
    </tr>
@empty
    <tr>
        <td colspan="4" class="py-4 px-4 text-center text-gray-500 dark:text-gray-400">
            予約投稿はありません。
        </td>
    </tr>
@endforelse
