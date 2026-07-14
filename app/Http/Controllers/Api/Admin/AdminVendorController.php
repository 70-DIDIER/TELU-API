<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminVendorController extends Controller
{
    /**
     * Paginated list of every vendor across the platform.
     * Filters: ?is_active=, ?search= (shop_name).
     */
    public function index(Request $request): JsonResponse
    {
        $vendors = Vendor::query()
            ->with('user:id,full_name,phone,email,status')
            ->withCount(['products', 'orders'])
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->filled('search'), fn ($q) => $q->where('shop_name', 'like', '%'.$request->string('search').'%'))
            ->latest()
            ->paginate(20);

        return response()->json($vendors);
    }

    /**
     * Show one vendor with owner and activity counts.
     */
    public function show(string $vendor): JsonResponse
    {
        $found = Vendor::query()
            ->with('user:id,full_name,phone,email,status')
            ->withCount(['products', 'orders'])
            ->find($vendor);

        if (! $found) {
            return response()->json(['message' => 'Vendeur introuvable.'], 404);
        }

        return response()->json($found);
    }

    /**
     * Activate or deactivate a vendor's shop (moderation).
     */
    public function updateStatus(Request $request, string $vendor): JsonResponse
    {
        $data = $request->validate(['is_active' => ['required', 'boolean']]);

        $found = Vendor::find($vendor);

        if (! $found) {
            return response()->json(['message' => 'Vendeur introuvable.'], 404);
        }

        $found->update(['is_active' => $data['is_active']]);

        return response()->json($found);
    }
}
