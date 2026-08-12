<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

/**
 * Settles the vendor and driver wallets for an order once BOTH conditions are
 * true — order delivered AND a successful Payment exists for it — regardless
 * of which happens first. Called from OrderController::confirmReceipt() and
 * PaymentController::applyGatewayStatus(). Idempotent via orders.wallet_settled_at.
 */
class CommerceLedger
{
    public static function settleOrderIfReady(Order $order): void
    {
        if ($order->wallet_settled_at !== null || $order->status !== 'delivered') {
            return;
        }

        $isPaid = Payment::query()
            ->where('reference_type', 'order')
            ->where('reference_id', $order->id)
            ->where('status', 'success')
            ->exists();

        if (! $isPaid) {
            return;
        }

        DB::transaction(function () use ($order) {
            // Re-check under lock: another process may have settled it meanwhile.
            $locked = Order::query()->lockForUpdate()->find($order->id);

            if (! $locked || $locked->wallet_settled_at !== null) {
                return;
            }

            $order->loadMissing(['vendor', 'delivery.driver']);

            if ($order->vendor) {
                $order->vendor->creditWallet(
                    (float) $order->vendor_net_amount,
                    'order',
                    $order->id,
                    "Vente — commande #{$order->id}"
                );
                Notifier::send(
                    $order->vendor->user_id,
                    'wallet',
                    "Votre portefeuille a été crédité de {$order->vendor_net_amount} FCFA pour la commande #{$order->id}."
                );
            }

            $delivery = $order->delivery;

            if ($delivery && $delivery->driver) {
                $delivery->driver->creditWallet(
                    (float) $delivery->driver_net_amount,
                    'delivery',
                    $delivery->id,
                    "Course — livraison #{$delivery->id}"
                );
                Notifier::send(
                    $delivery->driver->user_id,
                    'wallet',
                    "Votre portefeuille a été crédité de {$delivery->driver_net_amount} FCFA pour la livraison #{$delivery->id}."
                );
            }

            $locked->update(['wallet_settled_at' => now()]);
        });
    }
}
