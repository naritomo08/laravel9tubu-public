<?php

namespace App\View\Components\Tweet;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\HtmlString;
use Illuminate\View\Component;

class FormattedContent extends Component
{
    private const LINK_CLASSES = 'text-sky-600 underline hover:text-sky-500 dark:text-sky-400 dark:hover:text-sky-300';

    public function __construct(
        public readonly string $content,
    ) {
    }

    public function render(): View|Closure|string
    {
        return view('components.tweet.formatted-content');
    }

    public function formattedContent(): HtmlString
    {
        $escapedContent = e($this->content);

        $linkedContent = preg_replace_callback(
            '/((?:https?:\/\/|www\.)[^\s<]+)/iu',
            static function (array $matches): string {
                $detectedUrl = $matches[1];
                $linkedUrl = rtrim($detectedUrl, '.,!?;:)]}');
                $suffix = substr($detectedUrl, strlen($linkedUrl));

                if ($linkedUrl === '') {
                    return $detectedUrl;
                }

                $decodedUrl = html_entity_decode($linkedUrl, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $href = str_starts_with($decodedUrl, 'www.') ? 'https://' . $decodedUrl : $decodedUrl;

                return sprintf(
                    '<a href="%s" target="_blank" rel="noopener noreferrer" class="%s">%s</a>%s',
                    e($href),
                    self::LINK_CLASSES,
                    $linkedUrl,
                    $suffix
                );
            },
            $escapedContent
        );

        return new HtmlString(nl2br($linkedContent ?? $escapedContent));
    }
}
