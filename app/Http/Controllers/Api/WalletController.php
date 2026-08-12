<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    /**
     * The authenticated vendor's wallet (balance + latest transactions).
     */
    public function vendor(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;

        if (! $vendor instanceof Vendor) {
            return response()->json(['message' => 'Vous devez d\'abord créer votre profil vendeur.'], 403);
        }

        return $this->show($vendor);
    }

    /**
     * The authenticated driver's wallet (balance + latest transactions).
     */
    public function driver(Request $request): JsonResponse
    {
        $driver = $request->user()->driver;

        if (! $driver instanceof Driver) {
            return response()->json(['message' => 'Vous devez d\'abord créer votre profil livreur.'], 403);
        }

        return $this->show($driver);
    }

    private function show(Vendor|Driver $walletable): JsonResponse
    {
        $wallet = $walletable->walletOrCreate();
        $wallet->load(['transactions' => fn ($q) => $q->latest()->limit(50)]);

        return response()->json($wallet);
    }
}
