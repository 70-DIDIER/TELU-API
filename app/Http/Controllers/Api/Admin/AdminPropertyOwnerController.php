<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PropertyOwner;
use App\Services\Notifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminPropertyOwnerController extends Controller
{
    /**
     * Paginated list of every property owner.
     * Filters: ?owner_type=, ?search= (company_name).
     */
    public function index(Request $request): JsonResponse
    {
        $owners = PropertyOwner::query()
            ->with(['user:id,full_name,phone,email,status', 'subscription:id,name'])
            ->withCount(['properties', 'reservations'])
            ->when($request->filled('owner_type'), fn ($q) => $q->where('owner_type', $request->string('owner_type')))
            ->when($request->filled('search'), fn ($q) => $q->where('company_name', 'like', '%'.$request->string('search').'%'))
            ->latest()
            ->paginate(20);

        return response()->json($owners);
    }

    /**
     * Show one property owner with user and activity counts.
     */
    public function show(string $owner): JsonResponse
    {
        $found = PropertyOwner::query()
            ->with(['user:id,full_name,phone,email,status', 'subscription:id,name'])
            ->withCount(['properties', 'reservations'])
            ->find($owner);

        if (! $found) {
            return response()->json(['message' => 'Propriétaire introuvable.'], 404);
        }

        return response()->json($found);
    }

    /**
     * Approve or reject the owner's KYC document (id_document_url).
     * A rejection requires a reason so the owner knows what to fix and resubmit.
     */
    public function updateVerification(Request $request, string $owner): JsonResponse
    {
        $data = $request->validate([
            'verification_status' => ['required', Rule::in(['verified', 'rejected'])],
            'verification_notes' => ['nullable', 'string', 'max:1000', 'required_if:verification_status,rejected'],
        ]);

        $found = PropertyOwner::find($owner);

        if (! $found) {
            return response()->json(['message' => 'Propriétaire introuvable.'], 404);
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
                ? 'Vos documents ont été vérifiés : votre profil propriétaire est certifié.'
                : "Vos documents ont été rejetés : {$data['verification_notes']}",
            ['route' => $data['verification_status'] === 'verified' ? 'owner_space' : 'owner_complete_profile']
        );

        return response()->json($found);
    }
}
