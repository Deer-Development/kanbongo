<?php

namespace App\Services\Generators;

use Illuminate\Support\Facades\File;

class ResourceGenerator
{
    public static function generate($namespacePath, $namespaceForStub, $name): void
    {
        $resourceName = "{$name}Resource";
        $resourcePath = app_path("Http/Resources/{$namespacePath}/{$resourceName}.php");

        if (!File::exists(dirname($resourcePath))) {
            File::makeDirectory(dirname($resourcePath), 0755, true);
        }

        $stub = File::get(base_path("stubs/Resources/Resource.stub"));
        $stub = str_replace(['{{name}}', '{{namespace}}'], [$name, $namespaceForStub], $stub);

        File::put($resourcePath, $stub);
    }
}
