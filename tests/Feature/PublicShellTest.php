<?php

it('has every text in both languages', function () {
    $english = json_decode(file_get_contents(lang_path('en.json')), true);
    $nepali = json_decode(file_get_contents(lang_path('ne.json')), true);

    expect(array_keys($nepali))->toEqualCanonicalizing(array_keys($english))
        ->and(array_filter($nepali, fn (string $text) => ! preg_match('/\p{Devanagari}/u', $text)))->toBeEmpty();
})->group('P2');

it('opens in Nepali by default', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('<html lang="ne">', escape: false)
        ->assertSee('गुनासो सुनुवाइ')
        ->assertSee('<button type="submit" lang="en">English</button>', escape: false);
})->group('P2', 'S9');

it('switches to English and back, remembering the choice in a cookie', function () {
    $this->post('/language', ['locale' => 'en'])
        ->assertRedirect()
        ->assertCookie('locale', 'en');

    $this->withCookie('locale', 'en')->get('/')
        ->assertSee('<html lang="en">', escape: false)
        ->assertSee('Grievance handling')
        ->assertSee('<button type="submit" lang="ne">नेपाली</button>', escape: false);

    $this->post('/language', ['locale' => 'ne'])->assertCookie('locale', 'ne');
})->group('P2', 'S9');

it('returns the visitor to the page they were on', function () {
    $this->get('/');

    $this->post('/language', ['locale' => 'en'])->assertRedirect(url('/'));
})->group('P2');

it('works after the session has expired, without a CSRF token', function () {
    // CSRF checks are skipped in the testing environment, so run as production.
    app()->detectEnvironment(fn () => 'production');

    $this->post('https://localhost/language', ['locale' => 'en'])->assertStatus(302)->assertCookie('locale', 'en');
})->group('P2');

it('ignores languages it does not offer', function () {
    $this->post('/language', ['locale' => 'fr'])->assertSessionHasErrors('locale')->assertCookieMissing('locale');

    $this->withCookie('locale', 'fr')->get('/')->assertSee('<html lang="ne">', escape: false);
})->group('P2');

it('leaves the staff panel language alone', function () {
    expect($this->withCookie('locale', 'en')->get('/admin/login')->getContent())->toMatch('/<html\s+lang="ne"/');
})->group('P2');

it('styles public pages from one local stylesheet, with no inline style or script', function () {
    $html = $this->get('/')->getContent();

    expect($html)->toContain('<link rel="stylesheet" href="'.asset('css/public.css').'">')
        ->not->toMatch('/<script|<style|\sstyle=/i')
        ->and(filesize(public_path('css/public.css')))->toBeLessThan(15 * 1024);
})->group('P2', 'S9');
