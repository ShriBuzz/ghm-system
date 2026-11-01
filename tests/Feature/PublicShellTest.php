<?php

it('has every text in both languages', function () {
    $english = json_decode(file_get_contents(lang_path('en.json')), true);
    $nepali = json_decode(file_get_contents(lang_path('ne.json')), true);

    expect(array_keys($nepali))->toEqualCanonicalizing(array_keys($english))
        ->and(array_filter($nepali, fn (string $text) => ! preg_match('/\p{Devanagari}/u', $text)))->toBeEmpty();
})->group('P2');

it('sends a first visit to the Nepali pages', function () {
    $this->get('/')->assertRedirect(url('ne'));
})->group('P2');

it('sends a returning visitor to the language they used last', function () {
    $this->withCookie('locale', 'en')->get('/')->assertRedirect(url('en'));
    $this->withCookie('locale', 'fr')->get('/')->assertRedirect(url('ne'));
})->group('P2');

it('serves each language at its own address, with a link to the other', function (string $locale, string $title, string $other, string $otherName, string $label) {
    $this->get("/{$locale}")
        ->assertOk()
        ->assertSee("<html lang=\"{$locale}\">", escape: false)
        ->assertSee($title)
        ->assertSee('<a class="site-name" href="'.url($locale).'">', escape: false)
        ->assertSee("<nav class=\"language-switch\" aria-label=\"{$label}\">", escape: false)
        ->assertSee("<span lang=\"{$locale}\" aria-current=\"true\">", escape: false)
        ->assertSee('<a href="'.url($other)."\" lang=\"{$other}\" hreflang=\"{$other}\">{$otherName}</a>", escape: false)
        ->assertSee("<link rel=\"alternate\" hreflang=\"{$other}\" href=\"".url($other).'">', escape: false)
        ->assertCookie('locale', $locale);
})->with([
    'Nepali' => ['ne', 'गुनासो सुनुवाइ', 'en', 'English', 'भाषा'],
    'English' => ['en', 'Grievance handling', 'ne', 'नेपाली', 'Language'],
])->group('P2', 'S9');

it('keeps the cookie as it is when the language has not changed', function () {
    $this->withCookie('locale', 'en')->get('/en')->assertCookieMissing('locale');
})->group('P2');

it('offers no other languages', function () {
    $this->get('/fr')->assertNotFound();
})->group('P2');

it('leaves the staff panel language alone', function () {
    expect($this->withCookie('locale', 'ne')->get('/admin/login')->getContent())->toMatch('/<html\s+lang="en"/');
})->group('P2');

it('styles public pages from one local stylesheet, with no inline style or script', function () {
    $html = $this->get('/ne')->getContent();

    expect($html)->toContain('<link rel="stylesheet" href="'.asset('css/public.css').'">')
        ->not->toMatch('/<script|<style|\sstyle=/i')
        ->and(filesize(public_path('css/public.css')))->toBeLessThan(15 * 1024);
})->group('P2', 'S9');
