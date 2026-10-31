<?php

use App\Enums\Role;
use App\Models\Category;
use App\Models\Grievance;
use App\Models\User;

/**
 * @return array<string, Grievance>
 */
function casesOfEveryKind(): array
{
    return [
        'open' => Grievance::factory()->create(),
        'restricted category' => Grievance::factory()->for(Category::factory()->restricted())->create(),
        'marked restricted' => Grievance::factory()->create(['manually_restricted' => true]),
    ];
}

dataset('visibility', [
    'administrator' => [Role::Administrator, ['open', 'restricted category', 'marked restricted']],
    'focal person' => [Role::FocalPerson, ['open']],
    'restricted-case officer' => [Role::RestrictedOfficer, ['restricted category', 'marked restricted']],
]);

it('lists only the cases each role may see', function (Role $role, array $visible) {
    $cases = casesOfEveryKind();
    $user = User::factory()->create(['role' => $role]);

    expect(Grievance::visibleTo($user)->pluck('id')->sort()->values()->all())
        ->toBe(collect($cases)->only($visible)->pluck('id')->sort()->values()->all());
})->with('visibility')->group('A1', 'A5');

it('lets each role open and work only the cases it may see', function (Role $role, array $visible) {
    $user = User::factory()->create(['role' => $role]);

    foreach (casesOfEveryKind() as $kind => $case) {
        expect($user->can('view', $case))->toBe(in_array($kind, $visible), "view $kind")
            ->and($user->can('update', $case))->toBe(in_array($kind, $visible), "update $kind");
    }
})->with('visibility')->group('A1', 'A5');

it('lets every role enter offline cases and no role delete one', function (Role $role) {
    $user = User::factory()->create(['role' => $role]);

    expect($user->can('create', Grievance::class))->toBeTrue()
        ->and($user->can('delete', Grievance::factory()->create()))->toBeFalse();
})->with(Role::cases())->group('A1');

it('lets only the Administrator remove a restricted mark', function (Role $role) {
    $case = Grievance::factory()->create(['manually_restricted' => true]);
    $user = User::factory()->create(['role' => $role]);

    expect($user->can('removeRestriction', $case))->toBe($role === Role::Administrator);
})->with(Role::cases())->group('A5');

it('hides a case in a restricted category even when its stored flag lags behind', function () {
    $case = Grievance::factory()->for(Category::factory()->restricted())->create();
    // A bulk update skips the observer, as a save racing a category change would.
    Grievance::whereKey($case->id)->update(['is_restricted' => false]);

    $focal = User::factory()->create(['role' => Role::FocalPerson]);
    $officer = User::factory()->create(['role' => Role::RestrictedOfficer]);

    expect(Grievance::visibleTo($focal)->pluck('id')->all())->toBe([])
        ->and($focal->can('view', $case->fresh()))->toBeFalse()
        ->and(Grievance::visibleTo($officer)->pluck('id')->all())->toBe([$case->id]);
})->group('A5');
