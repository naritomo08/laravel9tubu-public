<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class LegalDocumentController extends Controller
{
    private const DOCUMENTS = [
        'terms' => [
            'title' => '利用規約',
            'path' => 'markdown/terms.md',
        ],
        'privacy' => [
            'title' => 'プライバシーポリシー',
            'path' => 'markdown/privacy.md',
        ],
    ];

    public function terms(): View
    {
        return $this->show('terms');
    }

    public function privacy(): View
    {
        return $this->show('privacy');
    }

    private function show(string $document): View
    {
        abort_unless(array_key_exists($document, self::DOCUMENTS), 404);

        $config = self::DOCUMENTS[$document];
        $path = resource_path($config['path']);

        abort_unless(is_readable($path), 404);

        return view('legal.show', [
            'title' => $config['title'],
            'content' => new HtmlString(Str::markdown(file_get_contents($path), [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ])),
        ]);
    }
}
