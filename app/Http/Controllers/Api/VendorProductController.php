<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorProductController extends Controller
{
    /**
     * List the products owned by the authenticated vendor.
     */
    public function index(Request $request): JsonResponse
    {
        $vendor = $this->vendorOrNull($request);

        if (! $vendor instanceof Vendor) {
            return $vendor; // JsonResponse with the 403 error.
        }

        return response()->json(
            $vendor->products()->latest()->paginate(20)
        );
    }

    /**
     * Create a product for the authenticated vendor.
     */
    public function store(ProductRequest $request): JsonResponse
    {
        $vendor = $this->vendorOrNull($request);

        if (! $vendor instanceof Vendor) {
            return $vendor;
        }

        $product = $vendor->products()->create($request->validated());

        return response()->json($product, 201);
    }

    /**
     * Show one of the authenticated vendor's products.
     */
    public function show(Request $request, string $product): JsonResponse
    {
        $vendor = $this->vendorOrNull($request);

        if (! $vendor instanceof Vendor) {
            return $vendor;
        }

        $found = $vendor->products()->find($product);

        if (! $found) {
            return response()->json(['message' => 'Produit introuvable ou non autorisé.'], 404);
        }

        return response()->json($found);
    }

    /**
     * Update one of the authenticated vendor's products.
     */
    public function update(ProductRequest $request, string $product): JsonResponse
    {
        $vendor = $this->vendorOrNull($request);

        if (! $vendor instanceof Vendor) {
            return $vendor;
        }

        $found = $vendor->products()->find($product);

        if (! $found) {
            return response()->json(['message' => 'Produit introuvable ou non autorisé.'], 404);
        }

        $found->update($request->validated());

        return response()->json($found);
    }

    /**
     * Delete one of the authenticated vendor's products.
     */
    public function destroy(Request $request, string $product): JsonResponse
    {
        $vendor = $this->vendorOrNull($request);

        if (! $vendor instanceof Vendor) {
            return $vendor;
        }

        $found = $vendor->products()->find($product);

        if (! $found) {
            return response()->json(['message' => 'Produit introuvable ou non autorisé.'], 404);
        }

        $found->delete();

        return response()->json(['message' => 'Produit supprimé.']);
    }

    /**
     * Resolve the authenticated user's vendor profile, or a 403 JsonResponse
     * when the account is not a vendor / has no vendor profile yet.
     */
    private function vendorOrNull(Request $request): Vendor|JsonResponse
    {
        $vendor = $request->user()->vendor;

        if (! $vendor) {
            return response()->json([
                'message' => 'Vous devez d\'abord créer votre profil vendeur.',
            ], 403);
        }

        return $vendor;
    }
}
