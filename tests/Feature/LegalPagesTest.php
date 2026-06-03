<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Coverage for the four public legal pages (framework, privacy, terms,
 * notices): they render in all three locales, the footer wiring points at the
 * real routes, and the registration form links to terms + privacy.
 */
class LegalPagesTest extends TestCase
{
    /** url => localized <h1> title, per locale. */
    private const TITLES = [
        'ar' => [
            '/legal/framework' => 'الإطار القانوني',
            '/legal/privacy' => 'سياسة الخصوصية',
            '/legal/terms' => 'شروط الاستخدام',
            '/legal/notices' => 'ملاحظات قانونية',
        ],
        'fr' => [
            '/legal/framework' => 'Cadre juridique',
            '/legal/privacy' => 'Politique de confidentialité',
            '/legal/terms' => 'Conditions d’utilisation',
            '/legal/notices' => 'Mentions légales',
        ],
        'en' => [
            '/legal/framework' => 'Legal Framework',
            '/legal/privacy' => 'Privacy Policy',
            '/legal/terms' => 'Terms of Use',
            '/legal/notices' => 'Legal Notices',
        ],
    ];

    public function test_all_legal_pages_render_in_arabic(): void
    {
        foreach (self::TITLES['ar'] as $url => $title) {
            $this->get($url)->assertOk()->assertSee($title);
        }
    }

    public function test_all_legal_pages_render_in_french(): void
    {
        $this->get('/lang/fr');

        foreach (self::TITLES['fr'] as $url => $title) {
            $this->get($url)
                ->assertOk()
                ->assertSee('dir="ltr"', false)
                ->assertSee($title);
        }
    }

    public function test_all_legal_pages_render_in_english(): void
    {
        $this->get('/lang/en');

        foreach (self::TITLES['en'] as $url => $title) {
            $this->get($url)->assertOk()->assertSee($title);
        }
    }

    public function test_each_legal_page_cross_links_to_the_other_three(): void
    {
        // The shared component renders a "related pages" block; every legal page
        // should link to the three siblings it is not currently on.
        $routes = ['legal.framework', 'legal.privacy', 'legal.terms', 'legal.notices'];

        foreach ($routes as $current) {
            $response = $this->get(route($current))->assertOk();

            foreach ($routes as $other) {
                if ($other !== $current) {
                    $response->assertSee(route($other), false);
                }
            }
        }
    }

    public function test_register_form_links_to_terms_and_privacy(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee(route('legal.terms'), false)
            ->assertSee(route('legal.privacy'), false);
    }
}
