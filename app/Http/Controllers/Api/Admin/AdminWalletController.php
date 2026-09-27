<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Vendor;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminWalletController extends Controller
{
    private const WALLETABLE_TYPES = [
        'vendor' => Vendor::class,
        'driver' => Driver::class,
    ];

    /**
     * Paginated list of every vendor/driver wallet (commission-based
     * earnings, net of platform commission). Filter: ?walletable_type=vendor|driver.
     */
    public function index(Request $request): JsonResponse
    {
        $wallets = Wallet::query()
            ->with('walletable.user:id,full_name,phone,email')
            ->when(
                $request->filled('walletable_type') && isset(self::WALLETABLE_TYPES[$request->string('walletable_type')->value()]),
                fn ($q) => $q->where('walletable_type', self::WALLETABLE_TYPES[$request->string('walletable_type')->value()])
            )
            ->latest('updated_at')
            ->paginate(20);

        return response()->json($wallets);
    }

    /**
     * Show one wallet with its owner and latest transactions.
     */
    public function show(string $wallet): JsonResponse
    {
        $found = Wallet::query()
            ->with(['walletable.user:id,full_name,phone,email', 'transactions' => fn ($q) => $q->latest()->limit(30)])
            ->find($wallet);

        if (! $found) {
            return response()->json(['message' => 'Portefeuille introuvable.'], 404);
        }

        return response()->json($found);
    }
}
