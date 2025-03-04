<?php

namespace App\Http\Controllers;

use App\Models\Container;
use App\Models\DocumentationTab;
use Illuminate\Http\Request;
use App\Http\Controllers\BaseController;
use Illuminate\Support\Str;
use App\Models\DocumentationVersion;

class DocumentationController extends BaseController
{
    public function index(Container $container)
    {
        return $this->successResponse(
            $container->documentationTabs()
                ->orderBy('order')
                ->get()
                , 'Documentation tabs fetched successfully'
        );
    }

    public function store(Request $request, Container $container)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $tab = $container->documentationTabs()->create([
            'name' => $validated['name'],
            'order' => $container->documentationTabs()->count()
        ]);

        return $this->successResponse(
            $tab,
            'Documentation tab created successfully'
        );
    }

    public function show(DocumentationTab $tab)
    {
        return $this->successResponse(
            $tab,
            'Documentation tab fetched successfully'
        );
    }

    public function update(Request $request, DocumentationTab $tab)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|nullable|string',
        ]);

        $tab->update($validated);

        return $this->successResponse(
            $tab,
            'Documentation tab updated successfully'
        );
    }

    public function destroy(DocumentationTab $tab)
    {
        $tab->delete();
        return $this->successResponse(
            null,
            'Documentation tab deleted successfully'
        );
    }

    public function updateOrder(Request $request)
    {
        $validated = $request->validate([
            'tabs' => 'required|array',
            'tabs.*' => 'required|integer|exists:documentation_tabs,id',
        ]);

        foreach ($validated['tabs'] as $index => $tabId) {
            DocumentationTab::where('id', $tabId)->update(['order' => $index]);
        }

        return $this->successResponse(
            null,
            'Documentation tabs order updated successfully'
        );
    }

    public function createVersion(Request $request, DocumentationTab $tab)
    {
        $validated = $request->validate([
            'comment' => 'required|string|max:255'
        ]);

        $lastVersion = $tab->versions()->latest('version_number')->first();
        $newVersionNumber = $lastVersion ? $lastVersion->version_number + 1 : 1;

        $version = $tab->versions()->create([
            'content' => $tab->content,
            'version_number' => $newVersionNumber,
            'created_by' => auth()->id(),
            'comment' => $validated['comment']
        ]);

        return $this->successResponse($version, 'Version created successfully');
    }

    public function getVersions(DocumentationTab $tab)
    {
        $versions = $tab->versions()
            ->with('creator:id,first_name,last_name')
            ->orderByDesc('version_number')
            ->get();

        return $this->successResponse($versions, 'Versions fetched successfully');
    }

    public function restoreVersion(DocumentationTab $tab, DocumentationVersion $version)
    {
        $tab->update(['content' => $version->content]);
        
        return $this->successResponse($tab, 'Version restored successfully');
    }
} 