<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Browse/search the public catalogue of available products.
     *
     * Supported query params: search, category, vendor_id, min_price, max_price.
     */
    public function index(Request $request): JsonResponse
    {
        $products = Product::query()
            ->where('is_available', true)
            ->with('vendor:id,shop_name,latitude,longitude')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->when($request->filled('vendor_id'), fn ($q) => $q->where('vendor_id', $request->string('vendor_id')))
            ->when($request->filled('min_price'), fn ($q) => $q->where('price', '>=', $request->float('min_price')))
            ->when($request->filled('max_price'), fn ($q) => $q->where('price', '<=', $request->float('max_price')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return response()->json($products);
    }

    /**
     * Show a single product with its vendor.
     */
    public function show(string $product): JsonResponse
    {
        $found = Product::query()
            ->where('is_available', true)
            ->with('vendor:id,shop_name,description,address,latitude,longitude')
            ->find($product);

        if (! $found) {
            return response()->json(['message' => 'Produit introuvable.'], 404);
        }

        return response()->json($found);
    }
}
