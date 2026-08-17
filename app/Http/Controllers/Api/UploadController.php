<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadRequest;
use Illuminate\Http\JsonResponse;

class UploadController extends Controller
{
    /**
     * Store a single image (product photo, verification document, avatar...)
     * on public storage and return its public URL, for use in another
     * resource's *_url field (e.g. product.image_url, vendor.id_document_url).
     *
     * The URL is built from the incoming request host rather than from
     * Storage::url(), which relies on APP_URL: in local development APP_URL is
     * http://localhost, and a phone on the LAN cannot reach that host — the
     * upload would succeed but the returned URL would never load.
     */
    public function store(UploadRequest $request): JsonResponse
    {
        $folder = $request->input('folder', 'misc');
        $path = $request->file('file')->store("uploads/{$folder}", 'public');

        return response()->json(['url' => url("/storage/{$path}")], 201);
    }
}
