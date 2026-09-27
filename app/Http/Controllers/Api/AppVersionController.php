<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class AppVersionController extends Controller
{
    /**
     * Public, unauthenticated: the mobile app calls this on every cold start
     * (before/independently of login) to decide whether to block behind a
     * forced-update screen. Values are Setting rows (group `app`, seeded
     * empty) — an admin bumps `min_app_version_*` after a store release to
     * propagate a forced update to every existing install without a new
     * client-side deploy. The client owns the actual semver comparison; this
     * just hands over the numbers.
     */
    public function show(): JsonResponse
    {
        return response()->json([
            'ios' => [
                'min_version' => Setting::get('min_app_version_ios', '1.0.0'),
                'latest_version' => Setting::get('latest_app_version_ios', '1.0.0'),
                'store_url' => Setting::get('app_store_url_ios', ''),
            ],
            'android' => [
                'min_version' => Setting::get('min_app_version_android', '1.0.0'),
                'latest_version' => Setting::get('latest_app_version_android', '1.0.0'),
                'store_url' => Setting::get('app_store_url_android', ''),
            ],
            'message' => Setting::get('app_update_message') ?: null,
        ]);
    }
}
