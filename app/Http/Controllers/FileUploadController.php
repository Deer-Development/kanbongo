<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class FileUploadController extends Controller
{
    public function uploadTempSpatie(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:5120',
        ]);

        $media = auth()->user()->addMedia($request->file('file'))->toMediaCollection('temporary-uploads');

        return response()->json([
            'id' => $media->id,
            'name' => $media->file_name,
            'url' => $media->getUrl(),
            'type' => $media->mime_type,
            'progress' => 100,
        ]);
    }

    public function deleteTempSpatie(Media $media)
    {
        $media->delete();
        return response()->json(['message' => 'Fișier șters din temporar.']);
    }
}

