<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BannerRequest;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;

class AdminBannerController extends Controller
{
    /**
     * List every banner (active or not), in display order.
     */
    public function index(): JsonResponse
    {
        $banners = Banner::query()->orderBy('position')->orderByDesc('created_at')->get();

        return response()->json($banners);
    }

    public function show(string $banner): JsonResponse
    {
        $found = Banner::find($banner);

        if (! $found) {
            return response()->json(['message' => 'Bannière introuvable.'], 404);
        }

        return response()->json($found);
    }

    public function store(BannerRequest $request): JsonResponse
    {
        $banner = Banner::create($request->validated());

        return response()->json($banner, 201);
    }

    public function update(BannerRequest $request, string $banner): JsonResponse
    {
        $found = Banner::find($banner);

        if (! $found) {
            return response()->json(['message' => 'Bannière introuvable.'], 404);
        }

        $found->update($request->validated());

        return response()->json($found);
    }

    public function destroy(string $banner): JsonResponse
    {
        $found = Banner::find($banner);

        if (! $found) {
            return response()->json(['message' => 'Bannière introuvable.'], 404);
        }

        $found->delete();

        return response()->json(['message' => 'Bannière supprimée.']);
    }
}
