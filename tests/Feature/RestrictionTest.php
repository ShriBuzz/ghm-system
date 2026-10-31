<?php

use App\Models\Category;
use App\Models\Grievance;

it('restricts a case in a restricted category or marked by hand', function () {
    expect(Grievance::factory()->create()->is_restricted)->toBeFalse()
        ->and(Grievance::factory()->for(Category::factory()->restricted())->create()->is_restricted)->toBeTrue()
        ->and(Grievance::factory()->create(['manually_restricted' => true])->is_restricted)->toBeTrue();
})->group('A5');

it('follows the case when its mark or category changes', function () {
    $case = Grievance::factory()->create(['manually_restricted' => true]);

    $case->forceFill(['manually_restricted' => false])->save();
    expect($case->fresh()->is_restricted)->toBeFalse();

    $case->category()->associate(Category::factory()->restricted()->create())->save();
    expect($case->fresh()->is_restricted)->toBeTrue();

    $case->forceFill(['manually_restricted' => true])->save();
    $case->category()->associate(Category::factory()->create())->save();
    expect($case->fresh()->is_restricted)->toBeTrue();
})->group('A5');

it('re-flags existing cases when a category becomes restricted or stops being so', function () {
    $category = Category::factory()->create();
    $plain = Grievance::factory()->for($category)->create();
    $marked = Grievance::factory()->for($category)->create(['manually_restricted' => true]);
    $elsewhere = Grievance::factory()->create();

    $category->forceFill(['is_restricted' => true])->save();

    expect($plain->fresh()->is_restricted)->toBeTrue()
        ->and($marked->fresh()->is_restricted)->toBeTrue()
        ->and($elsewhere->fresh()->is_restricted)->toBeFalse();

    $category->forceFill(['is_restricted' => false])->save();

    expect($plain->fresh()->is_restricted)->toBeFalse()
        ->and($marked->fresh()->is_restricted)->toBeTrue()
        ->and($elsewhere->fresh()->is_restricted)->toBeFalse();
})->group('A5');
