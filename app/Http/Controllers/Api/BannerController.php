<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;

class BannerController extends Controller
{
    /**
     * Public catalogue of active banners, in display order — feeds the
     * "Offres du jour" carousel on the mobile home screen.
     */
    public function index(): JsonResponse
    {
        $banners = Banner::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->orderByDesc('created_at')
            ->get(['id', 'image_url', 'link_url', 'title']);

        return response()->json($banners);
    }
}
