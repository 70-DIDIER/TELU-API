<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    /**
     * Store a single image (product photo, verification document, avatar...)
     * on public storage and return its public URL, for use in another
     * resource's *_url field (e.g. product.image_url, vendor.id_document_url).
     */
    public function store(UploadRequest $request): JsonResponse
    {
        $folder = $request->input('folder', 'misc');
        $path = $request->file('file')->store("uploads/{$folder}", 'public');

        return response()->json(['url' => Storage::disk('public')->url($path)], 201);
    }
}
