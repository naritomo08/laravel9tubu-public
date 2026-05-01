<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegalDocumentTest extends TestCase
{
    public function test_terms_page_renders_markdown_content()
    {
        $this->get('/terms')
            ->assertOk()
            ->assertSee('利用規約');
    }

    public function test_privacy_page_renders_markdown_content()
    {
        $this->get('/privacy')
            ->assertOk()
            ->assertSee('プライバシーポリシー');
    }

    public function test_legal_links_are_visible_on_guest_pages()
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('利用規約')
            ->assertSee('プライバシーポリシー')
            ->assertDontSee('お問い合わせ');
    }

    public function test_legal_links_are_visible_on_app_pages()
    {
        $this->get('/tweet')
            ->assertOk()
            ->assertSee('利用規約')
            ->assertSee('プライバシーポリシー')
            ->assertDontSee('お問い合わせ');
    }

    public function test_contact_link_is_visible_after_login()
    {
        $user = \App\Models\User::factory()->create();

        $this->actingAs($user)
            ->get('/tweet')
            ->assertOk()
            ->assertSee('利用規約')
            ->assertSee('プライバシーポリシー')
            ->assertSee('お問い合わせ');
    }
}
