<?php

namespace App\Http\Controllers;

use App\Models\Container;
use App\Models\DocumentationTab;
use Illuminate\Http\Request;
use App\Http\Controllers\BaseController;

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
} 