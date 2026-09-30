<?php

use Illuminate\Support\Facades\File;

it('keeps actions typed and exposes only handle', function () {
    $files = File::allFiles(app_path('Actions'));
    expect($files)->not->toBeEmpty();

    foreach ($files as $file) {
        $class = 'App\\Actions\\'.str_replace('/', '\\', substr($file->getRelativePathname(), 0, -4));
        $reflection = new ReflectionClass($class);
        $publicMethods = array_map(fn (ReflectionMethod $method): string => $method->name, $reflection->getMethods(ReflectionMethod::IS_PUBLIC));
        expect(array_diff($publicMethods, ['__construct', 'handle']))->toBe([], $class);
        expect($reflection->hasMethod('handle'))->toBeTrue($class);
        expect($reflection->getMethod('handle')->hasReturnType())->toBeTrue($class);

        foreach ($reflection->getMethod('handle')->getParameters() as $parameter) {
            $type = $parameter->getType();
            expect($type)->not->toBeNull($class.'::$'.$parameter->name);
            $types = $type instanceof ReflectionUnionType ? $type->getTypes() : [$type];
            foreach ($types as $type) {
                expect($type->getName())->not->toBeIn(['array', 'mixed', 'Illuminate\\Http\\Request', 'Symfony\\Component\\HttpFoundation\\Request']);
            }
        }

        expect($file->getContents())->not->toMatch('/\bauth\s*\(/')
            ->not->toContain('use Illuminate\\Http\\Request;');
    }
});

it('keeps DTOs final and readonly', function () {
    $files = File::allFiles(app_path('Data'));
    expect($files)->not->toBeEmpty();
    foreach ($files as $file) {
        $class = 'App\\Data\\'.str_replace('/', '\\', substr($file->getRelativePathname(), 0, -4));
        $reflection = new ReflectionClass($class);
        expect($reflection->isFinal())->toBeTrue($class);
        expect($reflection->isReadOnly())->toBeTrue($class);
    }
});
