<?php

namespace App\Services\Generators;

use Illuminate\Support\Facades\File;

class RequestGenerator
{
    public static function generate($namespacePath, $namespaceForStub, $name): void
    {
        $actions = ['Store', 'Update'];

        foreach ($actions as $action) {
            $requestName = "Validate{$name}{$action}";
            $requestPath = app_path("Http/Requests/{$namespacePath}/{$requestName}.php");

            if (!File::exists(dirname($requestPath))) {
                File::makeDirectory(dirname($requestPath), 0755, true);
            }

            $stub = File::get(base_path("stubs/Requests/{$action}Request.stub"));
            $stub = str_replace(['{{name}}', '{{namespace}}'], [$name, $namespaceForStub], $stub);

            File::put($requestPath, $stub);
        }
    }
}
