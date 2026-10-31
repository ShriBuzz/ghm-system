<?php

it('counts dates in Nepal time', function () {
    expect(now()->timezoneName)->toBe('Asia/Kathmandu');
})->group('A2');
