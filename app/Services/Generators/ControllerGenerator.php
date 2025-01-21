<?php

namespace App\Services\Generators;

use Illuminate\Support\Facades\File;

class ControllerGenerator
{
    public static function generate($namespacePath, $namespaceForStub, $pluralName, $name): void
    {
        $actions = ['Store', 'Update', 'Index', 'Show', 'Destroy'];

        foreach ($actions as $action) {
            $controllerPath = app_path("Http/Controllers/{$namespacePath}/{$action}.php");

            if (!File::exists(dirname($controllerPath))) {
                File::makeDirectory(dirname($controllerPath), 0755, true);
            }

            $stub = File::get(base_path("stubs/Controllers/{$action}Controller.stub"));
            $stub = str_replace(['{{name}}', '{{pluralName}}', '{{namespace}}'], [$name, $pluralName, $namespaceForStub], $stub);

            File::put($controllerPath, $stub);
        }
    }
}
