<?php

namespace Tests\Unit\View\Components\Tweet;

use App\View\Components\Tweet\FormattedContent;
use PHPUnit\Framework\TestCase;

class FormattedContentTest extends TestCase
{
    public function test_formatted_content_converts_urls_into_links(): void
    {
        $component = new FormattedContent("確認はこちら https://example.com/path?foo=1&bar=2\nwww.example.org.");

        $formattedContent = $component->formattedContent()->toHtml();

        $this->assertStringContainsString(
            '<a href="https://example.com/path?foo=1&amp;bar=2" target="_blank" rel="noopener noreferrer"',
            $formattedContent
        );
        $this->assertStringContainsString(
            '>https://example.com/path?foo=1&amp;bar=2</a>',
            $formattedContent
        );
        $this->assertStringContainsString(
            '<a href="https://www.example.org" target="_blank" rel="noopener noreferrer"',
            $formattedContent
        );
        $this->assertStringContainsString(
            '>www.example.org</a>.',
            $formattedContent
        );
        $this->assertStringContainsString("<br />\n", $formattedContent);
    }

    public function test_formatted_content_keeps_html_escaped(): void
    {
        $component = new FormattedContent('<script>alert(1)</script> https://example.com');

        $formattedContent = $component->formattedContent()->toHtml();

        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $formattedContent);
        $this->assertStringContainsString('<a href="https://example.com"', $formattedContent);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $formattedContent);
    }
}
