<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Services\Notifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

    /**
     * Approve or reject the vendor's KYC documents (id_document_url + rccm_document_url).
     * A rejection requires a reason so the vendor knows what to fix and resubmit.
     */
    public function updateVerification(Request $request, string $vendor): JsonResponse
    {
        $data = $request->validate([
            'verification_status' => ['required', Rule::in(['verified', 'rejected'])],
            'verification_notes' => ['nullable', 'string', 'max:1000', 'required_if:verification_status,rejected'],
        ]);

        $found = Vendor::find($vendor);

        if (! $found) {
            return response()->json(['message' => 'Vendeur introuvable.'], 404);
        }

        $found->update([
            'verification_status' => $data['verification_status'],
            'verification_notes' => $data['verification_notes'] ?? null,
            'verified_at' => now(),
        ]);

        Notifier::send(
            $found->user_id,
            'verification',
            $data['verification_status'] === 'verified'
                ? 'Vos documents ont été vérifiés : votre boutique est certifiée.'
                : "Vos documents ont été rejetés : {$data['verification_notes']}",
            ['route' => $data['verification_status'] === 'verified' ? 'vendor_space' : 'vendor_complete_profile']
        );

        return response()->json($found);
    }
}
