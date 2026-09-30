<?php

use App\Actions\Cms\Attribute\Group\DeleteAttributeGroupAction;
use App\Models\Attribute\AttributeGroup;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('authorizes every catalog and management action using the supplied actor', function () {
    $actor = User::factory()->create();
    $other = User::factory()->create();
    $this->actingAs($other);
    $paths = ['Cms/Attribute', 'Cms/Product/Category', 'Cms/Management/Role', 'Cms/Management/Permission', 'Cms/Management/Menu', 'Cms/Management/MenuSub'];

    foreach ($paths as $path) {
        foreach (File::allFiles(app_path('Actions/'.$path)) as $file) {
            $class = 'App\\Actions\\'.$path.'/'.substr($file->getRelativePathname(), 0, -4);
            $class = str_replace('/', '\\', $class);
            $method = new ReflectionMethod($class, 'handle');
            $arguments = [];
            foreach ($method->getParameters() as $parameter) {
                $type = $parameter->getType()->getName();
                $arguments[] = $type === User::class ? $actor : new $type(...array_map(
                    fn (ReflectionParameter $property): mixed => $property->isDefaultValueAvailable() ? $property->getDefaultValue() : match ($property->getType()->getName()) {
                        'string' => 'Test value', 'int' => 1, 'bool' => true, default => null,
                    },
                    (new ReflectionClass($type))->getConstructor()?->getParameters() ?? [],
                ));
            }
            $seenActor = null;
            Gate::before(function (User $user) use (&$seenActor): bool {
                $seenActor = $user;

                return false;
            });
            expect(fn () => $method->invokeArgs(app($class), $arguments))->toThrow(AuthorizationException::class);
            expect($seenActor?->id)->toBe($actor->id, $class);
        }
    }
});

it('creates and edits catalog groups through DTOs', function () {
    $actor = User::factory()->create();
    Gate::before(fn () => true);
    $component = Livewire::actingAs($actor)->test('cms.attribute.group.create-update')
        ->set('name', 'Ukuran')->set('description', 'Pilihan ukuran')->call('submit')->assertHasNoErrors();
    $group = AttributeGroup::where('name', 'Ukuran')->firstOrFail();
    $component->call('setAction', $group->id)->set('name', 'Ukuran Produk')->call('submit')->assertHasNoErrors();
    expect($group->fresh()->name)->toBe('Ukuran Produk');
    app(DeleteAttributeGroupAction::class)->handle($group, $actor);
    $this->assertModelMissing($group);
});
