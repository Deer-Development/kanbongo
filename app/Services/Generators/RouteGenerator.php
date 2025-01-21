<?php

namespace App\Services\Generators;

use Illuminate\Support\Facades\File;

class RouteGenerator
{
    public static function generate($namespacePath, $namespaceForStub, $nameSnake, $permissionPrefix): void
    {
        $routeFolder = base_path('routes/resources');

        if (!File::exists($routeFolder)) {
            File::makeDirectory($routeFolder, 0755, true);
        }

        $routeFilePath = "{$routeFolder}/{$nameSnake}.php";
        $routeContent = <<<PHP
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\\{$namespaceForStub}\\Index;
use App\Http\Controllers\\{$namespaceForStub}\\Store;
use App\Http\Controllers\\{$namespaceForStub}\\Show;
use App\Http\Controllers\\{$namespaceForStub}\\Update;
use App\Http\Controllers\\{$namespaceForStub}\\Destroy;

Route::group(['middleware' => ['permission:list-{$permissionPrefix}|view-{$permissionPrefix}|create-{$permissionPrefix}|edit-{$permissionPrefix}|delete-{$permissionPrefix}']
, 'prefix' => '{$permissionPrefix}'
], function () {
    Route::get('/', Index::class)->name("{$nameSnake}.index");
    Route::post('/', Store::class)->name("{$nameSnake}.store");
    Route::get('/{id}', Show::class)->name("{$nameSnake}.show");
    Route::put('/{id}', Update::class)->name("{$nameSnake}.update");
    Route::delete('/{id}', Destroy::class)->name("{$nameSnake}.destroy");
});
PHP;

        File::put($routeFilePath, $routeContent);
    }
}
