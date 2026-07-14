<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserStatusRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    /**
     * Paginated list of users with optional filters:
     * ?user_type=, ?status=, ?search= (full_name / phone / email).
     */
    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->when($request->filled('user_type'), fn ($q) => $q->where('user_type', $request->string('user_type')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('full_name', 'like', $term)
                        ->orWhere('phone', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->latest()
            ->paginate(20);

        return response()->json($users);
    }

    /**
     * Show one user with their business profile(s) and activity counts.
     */
    public function show(string $user): JsonResponse
    {
        $found = User::query()
            ->with([
                'vendor', 'driver', 'propertyOwner', 'recruiter', 'jobSeeker',
            ])
            ->withCount([
                'orders', 'reservations', 'payments',
            ])
            ->find($user);

        if (! $found) {
            return response()->json(['message' => 'Utilisateur introuvable.'], 404);
        }

        return response()->json($found);
    }

    /**
     * Activate or suspend a user account. An admin cannot suspend themselves.
     */
    public function updateStatus(UpdateUserStatusRequest $request, string $user): JsonResponse
    {
        $found = User::find($user);

        if (! $found) {
            return response()->json(['message' => 'Utilisateur introuvable.'], 404);
        }

        if ($found->id === $request->user()->id) {
            return response()->json([
                'message' => 'Vous ne pouvez pas modifier le statut de votre propre compte.',
            ], 422);
        }

        $status = $request->validated()['status'];

        // Suspending a user revokes their existing tokens so they are logged out.
        if ($status === 'suspended') {
            $found->tokens()->delete();
        }

        $found->update(['status' => $status]);

        return response()->json($found);
    }
}
