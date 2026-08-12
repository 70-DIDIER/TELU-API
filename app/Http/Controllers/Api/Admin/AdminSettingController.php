<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingRequest;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSettingController extends Controller
{
    /**
     * List every backoffice-editable setting (commission rates, delivery
     * pricing, free quotas, PayGate fee rates...). Filter: ?group=.
     */
    public function index(Request $request): JsonResponse
    {
        $settings = Setting::query()
            ->when($request->filled('group'), fn ($q) => $q->where('group', $request->string('group')))
            ->orderBy('group')
            ->orderBy('key')
            ->get();

        return response()->json($settings);
    }

    /**
     * Update the value of a single setting (identified by its key). Takes
     * effect on the next request: reads are memoised per-request only, and the
     * per-request memo is invalidated here so nothing stale survives the write.
     */
    public function update(SettingRequest $request, string $key): JsonResponse
    {
        $found = Setting::where('key', $key)->first();

        if (! $found) {
            return response()->json(['message' => 'Paramètre introuvable.'], 404);
        }

        $found->update(['value' => (string) $request->validated()['value']]);
        Setting::flushCache();

        return response()->json($found);
    }
}
