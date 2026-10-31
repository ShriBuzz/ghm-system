<?php

use Illuminate\Support\Facades\Schema;

it('has no IP address or device column in any table', function () {
    $columns = collect(Schema::getTableListing(schemaQualified: false))
        ->flatMap(fn (string $table) => collect(Schema::getColumnListing($table))->map(fn (string $column) => "$table.$column"));

    expect($columns)->not->toBeEmpty()
        ->and($columns->filter(fn (string $column) => preg_match('/(^|[._])ip(_|$)|user_?agent|device/i', $column)))->toBeEmpty();
})->group('S5');
