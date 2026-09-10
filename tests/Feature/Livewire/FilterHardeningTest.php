<?php

use App\Models\User;
use App\Traits\Livewire\WithChangeOrder;
use App\Traits\WithGetFilterData;
use App\Traits\WithGetFilterDataApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

it('rejects sorting fields not exposed by a Livewire component', function () {
    $component = new class
    {
        use WithChangeOrder;

        public string $paginationOrderBy = 'users.name';

        public string $paginationOrder = 'desc';

        public array $searchBy = [
            ['field' => 'users.name'],
        ];
    };

    try {
        $component->changeOrder('users.password');
    } catch (HttpException $exception) {
        expect($exception->getStatusCode())->toBe(422);

        return;
    }

    $this->fail('An invalid sort field was accepted.');
});

it('normalizes invalid table ordering and pagination values', function () {
    User::factory()->count(11)->create();

    $filter = new class
    {
        use WithGetFilterData;
    };

    $result = $filter->getDataWithFilter(
        model: User::query(),
        searchBy: [['field' => 'users.name']],
        orderBy: 'users.password',
        order: 'sideways',
        paginate: 999,
    );

    expect($result->perPage())->toBe(10)
        ->and($result->count())->toBe(10);
});

it('normalizes invalid API filters and pagination values', function () {
    User::factory()->count(11)->create();

    $filter = new class
    {
        use WithGetFilterDataApi;
    };

    $result = $filter->getDataWithFilter(
        model: User::query(),
        searchBy: ['name', 'email'],
        searchBySpecific: 'password',
        orderBy: 'password',
        order: 'sideways',
        paginate: 999,
    );

    expect($result->perPage())->toBe(10)
        ->and($result->count())->toBe(10);
});
