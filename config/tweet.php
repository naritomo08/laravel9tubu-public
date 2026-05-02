<?php

return [
    'content_max_length' => max(1, (int) env('TWEET_CONTENT_MAX_LENGTH', 140)),
    'daily_tweet_count_mail_time' => env('DAILY_TWEET_COUNT_MAIL_TIME', '07:00'),
];
