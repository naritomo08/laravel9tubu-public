@props([
    'tweets' => [],
])

@foreach($tweets as $tweet)
    <x-tweet.item :tweet="$tweet"></x-tweet.item>
@endforeach
