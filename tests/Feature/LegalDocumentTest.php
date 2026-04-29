<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegalDocumentTest extends TestCase
{
    public function test_terms_page_renders_markdown_content()
    {
        $this->get('/terms')
            ->assertOk()
            ->assertSee('利用規約')
            ->assertSee('つぶやき本文と画像の投稿、編集、削除');
    }

    public function test_privacy_page_renders_markdown_content()
    {
        $this->get('/privacy')
            ->assertOk()
            ->assertSee('プライバシーポリシー')
            ->assertSee('Googleアカウント識別子');
    }

    public function test_legal_links_are_visible_on_guest_pages()
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('利用規約')
            ->assertSee('プライバシーポリシー');
    }

    public function test_legal_links_are_visible_on_app_pages()
    {
        $this->get('/tweet')
            ->assertOk()
            ->assertSee('利用規約')
            ->assertSee('プライバシーポリシー');
    }
}
