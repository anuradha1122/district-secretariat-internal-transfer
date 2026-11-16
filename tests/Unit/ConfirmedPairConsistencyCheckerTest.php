<?php

use App\Services\Transfers\ConfirmedPairConsistencyChecker;

function consistencyCandidate(): array
{
    return [
        'application_a' => [
            'id' => 1,
            'office' => ['id' => 10],
        ],
        'application_b' => [
            'id' => 2,
            'office' => ['id' => 20],
        ],
        'rank_a' => 1,
        'rank_b' => 2,
    ];
}

function consistencyApplicationA(): array
{
    return [
        'id' => 1,
        'current_office_id' => 10,
        'preferences' => [
            ['office_id' => 20, 'priority' => 1],
        ],
    ];
}

function consistencyApplicationB(): array
{
    return [
        'id' => 2,
        'current_office_id' => 20,
        'preferences' => [
            ['office_id' => 10, 'priority' => 2],
        ],
    ];
}

test('unchanged offices and reciprocal preferences have no issues', function () {
    $checker = new ConfirmedPairConsistencyChecker();

    $issues = $checker->check(
        consistencyCandidate(),
        consistencyApplicationA(),
        consistencyApplicationB()
    );

    expect($issues)->toBe([]);
});

test('a changed current office is reported', function () {
    $checker = new ConfirmedPairConsistencyChecker();

    $applicationA = consistencyApplicationA();
    $applicationA['current_office_id'] = 30;

    $issues = $checker->check(
        consistencyCandidate(),
        $applicationA,
        consistencyApplicationB()
    );

    expect($issues)->toContain(
        'The first employee’s current office has changed.'
    );
});

test('a removed reciprocal preference is reported', function () {
    $checker = new ConfirmedPairConsistencyChecker();

    $applicationB = consistencyApplicationB();
    $applicationB['preferences'] = [];

    $issues = $checker->check(
        consistencyCandidate(),
        consistencyApplicationA(),
        $applicationB
    );

    expect($issues)->toContain(
        'The second employee no longer prefers the first employee’s current office.'
    );
});

test('a changed reciprocal preference priority is reported', function () {
    $checker = new ConfirmedPairConsistencyChecker();

    $applicationA = consistencyApplicationA();

    $applicationA['preferences'][0]['priority'] = 3;

    $issues = $checker->check(
        consistencyCandidate(),
        $applicationA,
        consistencyApplicationB()
    );

    expect($issues)->toContain(
        'The first employee’s reciprocal preference priority has changed.'
    );
});

test('a missing application is reported', function () {
    $checker = new ConfirmedPairConsistencyChecker();

    $issues = $checker->check(
        consistencyCandidate(),
        consistencyApplicationA(),
        null
    );

    expect($issues)->toBe([
        'One or both applications are unavailable.',
    ]);
});

test('an incomplete saved candidate is reported', function () {
    $checker = new ConfirmedPairConsistencyChecker();

    $issues = $checker->check(
        [],
        consistencyApplicationA(),
        consistencyApplicationB()
    );

    expect($issues)->toBe([
        'The saved candidate snapshot is incomplete.',
    ]);
});

test('applications in the wrong order are not compared as the saved pair', function () {
    $checker = new ConfirmedPairConsistencyChecker();

    $issues = $checker->check(
        consistencyCandidate(),
        consistencyApplicationB(),
        consistencyApplicationA()
    );

    expect($issues)->toBe([
        'The current applications do not match the saved pair.',
    ]);
});