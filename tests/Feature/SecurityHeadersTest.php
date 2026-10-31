<?php

use App\Models\User;
use Filament\Auth\MultiFactor\App\AppAuthentication;

it('sends plain HTTP to HTTPS outside local development', function (string $environment) {
    app()->detectEnvironment(fn () => $environment);

    $this->get('http://localhost/admin/login?x=1')->assertStatus(301)->assertRedirect('https://localhost/admin/login?x=1');
})->with(['production', 'staging'])->group('S1');

it('sends HSTS, no referrer and nosniff over HTTPS', function (string $path) {
    $this->get("https://localhost{$path}")
        ->assertHeader('Strict-Transport-Security', 'max-age=31536000')
        ->assertHeader('Referrer-Policy', 'no-referrer')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
})->with(['/ne', '/up', '/admin/login'])->group('S1');

it('marks every cookie Secure over HTTPS', function () {
    $cookies = $this->get('https://localhost/admin/login')->headers->getCookies();

    expect($cookies)->not->toBeEmpty();

    foreach ($cookies as $cookie) {
        expect($cookie->isSecure())->toBeTrue($cookie->getName());
    }
})->group('S1');

it('allows public pages to load only their own files', function (string $path) {
    $this->get($path)->assertHeader(
        'Content-Security-Policy',
        "default-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'",
    );
})->with(['/ne', '/en', '/up'])->group('S1');

it('allows inline scripts in the panel only with this response\'s nonce', function () {
    $user = User::factory()->create(['app_authentication_secret' => AppAuthentication::make()->generateSecret()]);

    foreach (['/admin/login', '/admin'] as $path) {
        $response = $path === '/admin' ? $this->actingAs($user)->get($path) : $this->get($path);
        $policy = $response->headers->get('Content-Security-Policy');

        expect($policy)->toContain("frame-ancestors 'none'")->not->toContain("script-src 'self' 'unsafe-inline'")
            ->and(preg_match("/'nonce-([^']+)'/", $policy, $nonce))->toBe(1);

        preg_match_all('/<script(?![^>]*\ssrc=)[^>]*>/', $response->getContent(), $inlineScripts);

        expect($inlineScripts[0])->not->toBeEmpty()->each->toContain("nonce=\"{$nonce[1]}\"");
    }
})->group('S1');
