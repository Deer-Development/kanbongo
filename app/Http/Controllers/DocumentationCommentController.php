<?php

namespace App\Http\Controllers;

use App\Models\DocumentationTab;
use App\Models\DocumentationComment;
use Illuminate\Http\Request;
use App\Http\Controllers\BaseController;

class DocumentationCommentController extends BaseController
{
    public function index(DocumentationTab $tab)
    {
        $comments = $tab->rootComments()->get();
        
        return $this->successResponse(
            $comments,
            'Comments fetched successfully'
        );
    }

    public function store(Request $request, DocumentationTab $tab)
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'selection_path' => 'nullable|array',
            'selection_offset' => 'nullable|integer',
            'selection_text' => 'nullable|string',
            'parent_id' => 'nullable|exists:documentation_comments,id'
        ]);

        $comment = $tab->comments()->create([
            'user_id' => auth()->id(),
            'content' => $validated['content'],
            'selection_path' => $validated['selection_path'] ?? null,
            'selection_offset' => $validated['selection_offset'] ?? null,
            'selection_text' => $validated['selection_text'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null
        ]);

        // Load the user and replies if it's a parent comment
        $comment->load(['user']);
        if (!$comment->parent_id) {
            $comment->load(['replies.user']);
        }

        return $this->successResponse(
            $comment,
            'Comment created successfully'
        );
    }

    public function update(Request $request, DocumentationComment $comment)
    {
        $validated = $request->validate([
            'content' => 'required|string'
        ]);

        $comment->update([
            'content' => $validated['content']
        ]);

        return $this->successResponse(
            $comment,
            'Comment updated successfully'
        );
    }

    public function destroy(DocumentationComment $comment)
    {
        $comment->delete();
        
        return $this->successResponse(
            null,
            'Comment deleted successfully'
        );
    }

    public function resolve(Request $request, DocumentationComment $comment)
    {
        $comment->update([
            'resolved_at' => now(),
            'resolved_by' => auth()->id()
        ]);

        $comment->load(['resolver']);
        
        return $this->successResponse(
            $comment,
            'Comment resolved successfully'
        );
    }

    public function unresolve(DocumentationComment $comment)
    {
        $comment->update([
            'resolved_at' => null,
            'resolved_by' => null
        ]);
        
        return $this->successResponse(
            $comment,
            'Comment unresolved successfully'
        );
    }
} 