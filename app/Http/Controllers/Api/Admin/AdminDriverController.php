<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Services\Notifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminDriverController extends Controller
{
    /**
     * Paginated list of every driver across the platform.
     * Filters: ?is_available=, ?search= (license_number or coverage_zone).
     */
    public function index(Request $request): JsonResponse
    {
        $drivers = Driver::query()
            ->with('user:id,full_name,phone,email,status')
            ->withCount('deliveries')
            ->when($request->has('is_available'), fn ($q) => $q->where('is_available', $request->boolean('is_available')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(fn ($q2) => $q2->where('license_number', 'like', $term)->orWhere('coverage_zone', 'like', $term));
            })
            ->latest()
            ->paginate(20);

        return response()->json($drivers);
    }

    /**
     * Show one driver with owner and activity counts.
     */
    public function show(string $driver): JsonResponse
    {
        $found = Driver::query()
            ->with('user:id,full_name,phone,email,status')
            ->withCount('deliveries')
            ->find($driver);

        if (! $found) {
            return response()->json(['message' => 'Chauffeur introuvable.'], 404);
        }

        return response()->json($found);
    }

    /**
     * Approve or reject the driver's KYC documents (id_document_url + vehicle_photo_url).
     * A rejection requires a reason so the driver knows what to fix and resubmit.
     */
    public function updateVerification(Request $request, string $driver): JsonResponse
    {
        $data = $request->validate([
            'verification_status' => ['required', Rule::in(['verified', 'rejected'])],
            'verification_notes' => ['nullable', 'string', 'max:1000', 'required_if:verification_status,rejected'],
        ]);

        $found = Driver::find($driver);

        if (! $found) {
            return response()->json(['message' => 'Chauffeur introuvable.'], 404);
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
                ? 'Vos documents ont été vérifiés : votre profil chauffeur est certifié.'
                : "Vos documents ont été rejetés : {$data['verification_notes']}",
            ['route' => $data['verification_status'] === 'verified' ? 'driver_space' : 'driver_complete_profile']
        );

        return response()->json($found);
    }
}
