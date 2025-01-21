<?php

namespace App\Console\Commands;

use App\Services\Generators\ControllerGenerator;
use App\Services\Generators\RequestGenerator;
use App\Services\Generators\RouteGenerator;
use App\Services\Generators\ServiceGenerator;
use App\Services\Generators\ResourceGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GenerateCrud extends Command
{
    protected $signature = 'alex:crud {name}';
    protected $description = 'Generate Model, Migration, CRUD Controllers, Requests, Roles & Permissions, and Routes';

    public function handle(): void
    {
        $namePath = $this->argument('name');
        $nameParts = explode('/', $namePath);
        $name = end($nameParts);
        $nameSnake = Str::snake($name);
        $namespacePath = implode('/', array_map('ucfirst', $nameParts));
        $namespaceForStub = implode('\\', array_map('ucfirst', $nameParts));
        $permissionPrefix = strtolower(implode('-', array_map(fn($part) => Str::slug(Str::snake($part)), $nameParts)));
        $pluralName = Str::plural($nameSnake);

        $this->generateModelAndMigration($name, $pluralName);

        $this->updateModel(ucfirst($name));

        $this->generateFilter(ucfirst($name));

        ServiceGenerator::generate($namespacePath, $namespaceForStub, $name);
        ControllerGenerator::generate($namespacePath, $namespaceForStub, $pluralName, $name);
        RequestGenerator::generate($namespacePath, $namespaceForStub, $name);
        RouteGenerator::generate($namespacePath, $namespaceForStub, $nameSnake, $permissionPrefix);
        ResourceGenerator::generate($namespacePath, $namespaceForStub, $name);

        $this->displayNextSteps($name, $permissionPrefix);

        $this->info("CRUD for {$name} generated successfully in {$namespacePath}!");
    }

    /**
     * Generate Model and Migration.
     *
     * @param string $name
     * @param string $pluralName
     * @return void
     */
    protected function generateModelAndMigration(string $name, string $pluralName): void
    {
        $this->call('make:model', [
            'name' => ucfirst($name)
        ]);

        $this->generateMigrationFromStub($name, $pluralName);
    }

    /**
     * Generate Migration using the custom stub.
     *
     * @param string $name
     * @param string $pluralName
     * @return void
     */
    protected function generateMigrationFromStub(string $name, string $pluralName): void
    {
        $migrationName = "create_{$pluralName}_table";
        $migrationClassName = Str::studly($migrationName);
        $stubPath = base_path('stubs/migration.stub');
        $migrationPath = database_path('migrations/' . date('Y_m_d_His') . "_{$migrationName}.php");

        $stub = File::get($stubPath);
        $stub = str_replace(
            ['{{pluralClassName}}', '{{singularClassName}}', '{{tableName}}', '{{singularName}}'],
            [Str::studly($pluralName), Str::studly($name), $pluralName, $name],
            $stub
        );

        File::put($migrationPath, $stub);
    }

    /**
     * Updates the Model to include Soft Deletes, Filtering, and other configurations.
     *
     * @param string $modelName
     * @return void
     */
    protected function updateModel($modelName): void
    {
        $modelPath = app_path("Models/{$modelName}.php");
        $content = File::get($modelPath);

        $content = preg_replace('/use\s+Illuminate\\\Database\\\Eloquent\\\Factories\\\HasFactory;\n?/', '', $content);

        $useStatements = [
            "use Illuminate\\Database\\Eloquent\\SoftDeletes;",
            "use App\\Traits\\Filterable;"
        ];

        foreach ($useStatements as $statement) {
            if (!Str::contains($content, trim($statement))) {
                $content = preg_replace(
                    '/(namespace\s+[^\s;]+;)/',
                    "$1\n$statement",
                    $content
                );
            }
        }

        $content = preg_replace('/use\s+SoftDeletes\s*;|use\s+Filterable\s*;|use\s+HasFactory\s*;/', '', $content);

        $content = preg_replace(
            '/class\s+' . $modelName . '\s+extends\s+Model\s*\{/',
            "class {$modelName} extends Model\n{\n    use SoftDeletes, Filterable;\n",
            $content
        );

        if (!Str::contains($content, "protected \$guarded")) {
            $content = preg_replace(
                '/(use\s+SoftDeletes,\s+Filterable;\n)/',
                "$1\n    protected \$guarded = ['id'];\n",
                $content
            );
        }

        $content = preg_replace('/{\s*{/', '{', $content);
        $content = preg_replace('/}\s*}/', '}', $content);

        File::put($modelPath, $content);
    }





    /**
     * Generate a Filter class that extends BaseFilter.
     *
     * @param string $modelName
     * @return void
     */
    protected function generateFilter(string $modelName): void
    {
        $filterClassName = "{$modelName}Filter";
        $filterPath = app_path("Filters/{$filterClassName}.php");

        if (!File::exists($filterPath)) {
            $stub = <<<EOT
<?php

namespace App\Filters;

use App\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Builder;

class {$filterClassName} extends BaseFilter
{
    /**
     * Override filter type definitions.
     *
     * @param string \$filter
     * @return string|null
     */
    protected function getFilterType(string \$filter): ?string
    {
        \$types = [
            // Define your filter types
        ];

        return \$types[\$filter] ?? null;
    }

    /**
     * Example of a custom filter.
     *
     * @param Builder \$query
     * @param mixed \$value
     * @return void
     */
    protected function filterCustomLogic(Builder \$query, \$value): void
    {
        // Define your custom filtering logic
    }
}
EOT;
            File::ensureDirectoryExists(app_path("Filters"));
            File::put($filterPath, $stub);
        }
    }

    /**
     * Displays the next steps after generating the CRUD.
     *
     * @param string $name
     * @param string $permissionPrefix
     * @return void
     */
    protected function displayNextSteps($name, $permissionPrefix): void
    {
        $migrationName = "create_" . Str::plural(Str::snake($name)) . "_table";

        $this->info("- Run `php artisan migrate` to create the {$name} table.");
        $this->info("- Include the routes in `api.php` using:");
        $this->line("  require base_path('routes/resources/{$permissionPrefix}.php');");
        $this->info("- Generated migration: `database/migrations/{$migrationName}`");
    }
}
