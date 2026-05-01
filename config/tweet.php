<?php

return [
    'content_max_length' => max(1, (int) env('TWEET_CONTENT_MAX_LENGTH', 140)),
];
