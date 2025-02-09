<?php

namespace App\Http\Controllers\Comment;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Comment\ValidateCommentStore;
use App\Models\Comment;
use App\Services\Comment\CommentService;
use App\Http\Resources\Comment\CommentResource;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\JsonResponse;

class UploadFile extends BaseController
{
    protected $service;

    public function __construct(CommentService $service)
    {
        $this->service = $service;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:5120', // Limită 5MB
        ]);

        $message = Comment::findOrFail($request->message_id);
        $media = $message->addMedia($request->file('file'))->toMediaCollection('attachments');

        return response()->json([
            'id' => $media->id,
            'name' => $media->file_name,
            'url' => $media->getUrl(),
            'type' => $media->mime_type,
        ]);
    }
}
