<?php

namespace App\Services\Generators;

use Illuminate\Support\Facades\File;

class ServiceGenerator
{
    public static function generate($namespacePath, $namespaceForStub, $name): void
    {
        $serviceName = "{$name}Service";
        $servicePath = app_path("Services/{$namespacePath}/{$serviceName}.php");

        if (!File::exists(dirname($servicePath))) {
            File::makeDirectory(dirname($servicePath), 0755, true);
        }

        $stub = File::get(base_path("stubs/Services/Service.stub"));
        $stub = str_replace(['{{name}}', '{{namespace}}'], [$name, $namespaceForStub], $stub);

        File::put($servicePath, $stub);
    }
}
