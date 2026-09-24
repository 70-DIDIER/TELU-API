<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

/**
 * Settles the vendor and driver wallets for an order once BOTH conditions are
 * true — order delivered AND a successful Payment exists for it — regardless
 * of which happens first. Called from OrderController::confirmReceipt(),
 * DriverDeliveryController::deliver() and PaymentController::applyGatewayStatus().
 * Idempotent via orders.wallet_settled_at.
 *
 * Also owns the reverse path: refundIfPaid() flags the payment of a cancelled
 * order as refunded (vendor cancel, customer cancel, or a payment that only
 * succeeds after the order was cancelled).
 */
class CommerceLedger
{
    /**
     * Flag any successful payment for a cancelled order as refunded and notify
     * the customer. The actual mobile-money disbursement is processed out of
     * band by an admin (same manual flow as withdrawals); this closes the audit
     * trail so a paid-then-cancelled order never silently keeps the customer's
     * money. Safe because a cancellable order is never wallet-settled
     * (settlement only happens at 'delivered').
     */
    public static function refundIfPaid(Order $order): void
    {
        $payments = Payment::query()
            ->where('reference_type', 'order')
            ->where('reference_id', $order->id)
            ->where('status', 'success')
            ->get();

        foreach ($payments as $payment) {
            $payment->update(['status' => 'refunded']);

            Notifier::send(
                $order->customer_id,
                'payment',
                "Votre commande a été annulée. Le remboursement de {$payment->amount} FCFA est en cours de traitement."
            );
        }
    }

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
