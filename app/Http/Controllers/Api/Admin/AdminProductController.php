<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminProductController extends Controller
{
    /**
     * Paginated list of every product across all vendors.
     * Filters: ?vendor_id=, ?category=, ?is_available=, ?search= (name).
     */
    public function index(Request $request): JsonResponse
    {
        $products = Product::query()
            ->with('vendor:id,shop_name')
            ->when($request->filled('vendor_id'), fn ($q) => $q->where('vendor_id', $request->string('vendor_id')))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->when($request->has('is_available'), fn ($q) => $q->where('is_available', $request->boolean('is_available')))
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%'))
            ->latest()
            ->paginate(20);

        return response()->json($products);
    }

    /**
     * Show one product with its vendor.
     */
    public function show(string $product): JsonResponse
    {
        $found = Product::query()
            ->with('vendor:id,shop_name')
            ->find($product);

        if (! $found) {
            return response()->json(['message' => 'Produit introuvable.'], 404);
        }

        return response()->json($found);
    }

    /**
     * Take a product down or back up (moderation of the catalogue).
     */
    public function updateAvailability(Request $request, string $product): JsonResponse
    {
        $data = $request->validate(['is_available' => ['required', 'boolean']]);

        $found = Product::find($product);

        if (! $found) {
            return response()->json(['message' => 'Produit introuvable.'], 404);
        }

        $found->update(['is_available' => $data['is_available']]);

        return response()->json($found);
    }
}
