<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Recruiter;
use App\Services\Notifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminRecruiterController extends Controller
{
    /**
     * Paginated list of every recruiter.
     * Filters: ?industry=, ?search= (company_name).
     */
    public function index(Request $request): JsonResponse
    {
        $recruiters = Recruiter::query()
            ->with(['user:id,full_name,phone,email,status', 'subscription:id,name'])
            ->withCount(['jobOffers', 'applications'])
            ->when($request->filled('industry'), fn ($q) => $q->where('industry', $request->string('industry')))
            ->when($request->filled('search'), fn ($q) => $q->where('company_name', 'like', '%'.$request->string('search').'%'))
            ->latest()
            ->paginate(20);

        return response()->json($recruiters);
    }

    /**
     * Show one recruiter with user and activity counts.
     */
    public function show(string $recruiter): JsonResponse
    {
        $found = Recruiter::query()
            ->with(['user:id,full_name,phone,email,status', 'subscription:id,name'])
            ->withCount(['jobOffers', 'applications'])
            ->find($recruiter);

        if (! $found) {
            return response()->json(['message' => 'Recruteur introuvable.'], 404);
        }

        return response()->json($found);
    }

    /**
     * Approve or reject the recruiter's KYC documents (id_document_url + company_document_url).
     * A rejection requires a reason so the recruiter knows what to fix and resubmit.
     */
    public function updateVerification(Request $request, string $recruiter): JsonResponse
    {
        $data = $request->validate([
            'verification_status' => ['required', Rule::in(['verified', 'rejected'])],
            'verification_notes' => ['nullable', 'string', 'max:1000', 'required_if:verification_status,rejected'],
        ]);

        $found = Recruiter::find($recruiter);

        if (! $found) {
            return response()->json(['message' => 'Recruteur introuvable.'], 404);
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
                ? 'Vos documents ont été vérifiés : votre profil recruteur est certifié.'
                : "Vos documents ont été rejetés : {$data['verification_notes']}",
            ['route' => $data['verification_status'] === 'verified' ? 'recruiter_space' : 'recruiter_complete_profile']
        );

        return response()->json($found);
    }
}
