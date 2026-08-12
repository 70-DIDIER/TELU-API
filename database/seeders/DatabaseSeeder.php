<?php

namespace Database\Seeders;

use App\Models\Delivery;
use App\Models\Driver;
use App\Models\JobApplication;
use App\Models\JobOffer;
use App\Models\JobSeeker;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Property;
use App\Models\PropertyOwner;
use App\Models\Rating;
use App\Models\Recruiter;
use App\Models\Reservation;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with a coherent sample dataset.
     */
    public function run(): void
    {
        // --- Backoffice settings (commission rates, delivery pricing, quotas) -
        $this->call(SettingSeeder::class);

        // --- Subscription plans (only property owners/recruiters subscribe —
        // vendors/drivers are monetised by commission, see CommerceLedger/HasWallet)
        $ownerPlan = Subscription::factory()->create([
            'name' => 'Vitrine Immobilier',
            'subscriber_type' => 'property_owner',
        ]);

        $recruiterPlan = Subscription::factory()->create([
            'name' => 'Recruteur Pro',
            'subscriber_type' => 'recruiter',
        ]);

        // --- Well-known accounts for manual testing -------------------------
        User::factory()->type('admin')->create([
            'full_name' => 'Admin TELU',
            'email' => 'admin@telu.tg',
            'phone' => '+228 90 00 00 00',
            'is_verified' => true,
        ]);

        $testClient = User::factory()->type('client')->create([
            'full_name' => 'Test Client',
            'email' => 'client@telu.tg',
            'phone' => '+228 91 00 00 00',
            'is_verified' => true,
        ]);

        // Pool of clients that place orders / reservations / receive messages.
        $clients = User::factory()->type('client')->count(15)->create()->push($testClient);

        // --- Well-known business-actor accounts (password "password"), one per
        // user_type, for manually testing every mobile flow end-to-end -------
        $testVendorUser = User::factory()->type('vendor')->create([
            'full_name' => 'Vendeur Test',
            'email' => 'vendor@telu.tg',
            'phone' => '+228 92 00 00 00',
            'is_verified' => true,
        ]);
        $testVendor = Vendor::factory()->create([
            'user_id' => $testVendorUser->id,
            'shop_name' => 'Boutique TELU Test',
            'is_active' => true,
        ]);
        Product::factory()->count(6)->create(['vendor_id' => $testVendor->id, 'is_available' => true, 'stock' => 25]);
        // Commerce est monétisé par commission (pas d'abonnement) : le portefeuille
        // est directement testable avec un solde de départ.
        $testVendor->creditWallet(27500, 'order', null, 'Solde de test (seed)');

        $testDriverUser = User::factory()->type('driver')->create([
            'full_name' => 'Livreur Test',
            'email' => 'driver@telu.tg',
            'phone' => '+228 93 00 00 00',
            'is_verified' => true,
        ]);
        $testDriver = Driver::factory()->create([
            'user_id' => $testDriverUser->id,
            'is_available' => true,
        ]);
        // Livraison est monétisée par commission (pas d'abonnement).
        $testDriver->creditWallet(8500, 'delivery', null, 'Solde de test (seed)');

        $testOwnerUser = User::factory()->type('property_owner')->create([
            'full_name' => 'Propriétaire Test',
            'email' => 'owner@telu.tg',
            'phone' => '+228 94 00 00 00',
            'is_verified' => true,
        ]);
        $testOwner = PropertyOwner::factory()->create([
            'user_id' => $testOwnerUser->id,
            'owner_type' => 'hotel',
            'company_name' => 'Hôtel TELU Test',
            // Abonnement démarré aujourd'hui, garanti actif : ses biens apparaissent
            // "Sponsorisé" dans le catalogue.
            'subscription_id' => $ownerPlan->id,
            'subscription_started_at' => now(),
            'subscription_expires_at' => now()->addDays($ownerPlan->duration_days),
        ]);
        Property::factory()->count(3)->create(['owner_id' => $testOwner->id, 'is_available' => true]);

        $testRecruiterUser = User::factory()->type('recruiter')->create([
            'full_name' => 'Recruteur Test',
            'email' => 'recruiter@telu.tg',
            'phone' => '+228 95 00 00 00',
            'is_verified' => true,
        ]);
        $testRecruiter = Recruiter::factory()->create([
            'user_id' => $testRecruiterUser->id,
            'company_name' => 'TELU Recrutement Test',
            // Pas d'abonnement, déjà au quota gratuit (3 offres) : la création d'une
            // 4e offre déclenche immédiatement le 403 "quota atteint" côté mobile.
            ...$this->subscriptionState(null),
        ]);
        JobOffer::factory()->count(3)->create(['recruiter_id' => $testRecruiter->id, 'is_active' => true]);

        $testJobSeekerUser = User::factory()->type('job_seeker')->create([
            'full_name' => 'Chercheur Test',
            'email' => 'jobseeker@telu.tg',
            'phone' => '+228 96 00 00 00',
            'is_verified' => true,
        ]);
        JobSeeker::factory()->create([
            'user_id' => $testJobSeekerUser->id,
            'profession' => 'Menuisier',
            'availability' => 'immediate',
        ]);

        // --- Delivery drivers ----------------------------------------------
        $drivers = collect(range(1, 5))->map(fn () => Driver::factory()->create());

        // --- Commerce: vendors -> products -> orders -> items/delivery/payment
        collect(range(1, 8))->each(function () use ($clients, $drivers) {
            $vendor = Vendor::factory()->create();

            $products = Product::factory()
                ->count(fake()->numberBetween(4, 10))
                ->create(['vendor_id' => $vendor->id]);

            // A handful of orders per vendor.
            Order::factory()
                ->count(fake()->numberBetween(2, 6))
                ->make(['vendor_id' => $vendor->id])
                ->each(function (Order $order) use ($products, $clients, $drivers): void {
                    $order->customer_id = $clients->random()->id;
                    $order->save();

                    // Order items drawn from this vendor's own products.
                    $lineProducts = $products->random(fake()->numberBetween(1, min(4, $products->count())));
                    $total = 0;

                    foreach ($lineProducts as $product) {
                        $quantity = fake()->numberBetween(1, 5);
                        OrderItem::factory()->create([
                            'order_id' => $order->id,
                            'product_id' => $product->id,
                            'quantity' => $quantity,
                            'unit_price' => $product->price,
                        ]);
                        $total += $quantity * (float) $product->price;
                    }

                    $order->update(['total_amount' => $total]);

                    // Delivery: assigned/delivered orders get a driver.
                    $delivery = match ($order->status) {
                        'delivered' => Delivery::factory()->delivered(),
                        'in_delivery' => Delivery::factory()->assigned(),
                        default => Delivery::factory(),
                    };
                    $delivery->create([
                        'order_id' => $order->id,
                        'driver_id' => \in_array($order->status, ['in_delivery', 'delivered'], true)
                            ? $drivers->random()->id
                            : null,
                    ]);

                    // Payment for orders that were not cancelled.
                    if ($order->status !== 'cancelled') {
                        $payment = $order->status === 'delivered'
                            ? Payment::factory()->successful()
                            : Payment::factory();
                        $payment->create([
                            'user_id' => $order->customer_id,
                            'amount' => $total,
                            'reference_type' => 'order',
                            'reference_id' => $order->id,
                        ]);
                    }
                });
        });

        // --- Real estate: owners -> properties -> reservations --------------
        collect(range(1, 5))->each(function () use ($clients, $ownerPlan) {
            $owner = PropertyOwner::factory()->create(
                $this->subscriptionState(fake()->boolean(40) ? $ownerPlan : null)
            );

            Property::factory()
                ->count(fake()->numberBetween(1, 4))
                ->create(['owner_id' => $owner->id])
                ->each(function (Property $property) use ($clients): void {
                    Reservation::factory()
                        ->count(fake()->numberBetween(0, 3))
                        ->make(['property_id' => $property->id])
                        ->each(function (Reservation $reservation) use ($clients): void {
                            $reservation->customer_id = $clients->random()->id;
                            $reservation->save();

                            if (\in_array($reservation->status, ['confirmed', 'completed'], true)) {
                                Payment::factory()->successful()->create([
                                    'user_id' => $reservation->customer_id,
                                    'amount' => $reservation->total_price,
                                    'reference_type' => 'reservation',
                                    'reference_id' => $reservation->id,
                                ]);
                            }
                        });
                });
        });

        // --- Jobs: recruiters -> offers, and seekers -> applications --------
        $jobSeekers = JobSeeker::factory()->count(10)->create();

        collect(range(1, 4))->each(function () use ($jobSeekers, $recruiterPlan) {
            $recruiter = Recruiter::factory()->create(
                $this->subscriptionState(fake()->boolean(40) ? $recruiterPlan : null)
            );

            JobOffer::factory()
                ->count(fake()->numberBetween(1, 3))
                ->create(['recruiter_id' => $recruiter->id])
                ->each(function (JobOffer $offer) use ($jobSeekers): void {
                    $jobSeekers
                        ->random(fake()->numberBetween(0, 4))
                        ->each(fn (JobSeeker $seeker) => JobApplication::factory()->create([
                            'job_offer_id' => $offer->id,
                            'job_seeker_id' => $seeker->id,
                        ]));
                });
        });

        // --- Cross-cutting: messages, ratings, notifications ----------------
        $allUsers = User::all();

        Message::factory()->count(30)->make()->each(function (Message $message) use ($allUsers): void {
            $pair = $allUsers->random(2);
            $message->sender_id = $pair->first()->id;
            $message->receiver_id = $pair->last()->id;
            $message->save();
        });

        // Ratings targeting vendors and drivers.
        Vendor::all()->each(fn (Vendor $vendor) => Rating::factory()
            ->count(fake()->numberBetween(0, 5))
            ->forTarget('vendor', $vendor->id)
            ->create(['rater_id' => $clients->random()->id]));

        $drivers->each(fn (Driver $driver) => Rating::factory()
            ->count(fake()->numberBetween(0, 4))
            ->forTarget('driver', $driver->id)
            ->create(['rater_id' => $clients->random()->id]));

        // A few notifications for the test client plus random users.
        Notification::factory()->count(4)->create(['user_id' => $testClient->id]);
        $allUsers->random(10)->each(fn (User $user) => Notification::factory()
            ->count(fake()->numberBetween(1, 3))
            ->create(['user_id' => $user->id]));
    }

    /**
     * subscription_id + a realistic started_at/expires_at window (or all-null)
     * for either subscriber profile (property_owner/recruiter — the only two
     * that actually subscribe; vendors/drivers are commission-based).
     *
     * @return array<string, mixed>
     */
    private function subscriptionState(?Subscription $plan): array
    {
        if (! $plan) {
            return ['subscription_id' => null, 'subscription_started_at' => null, 'subscription_expires_at' => null];
        }

        $startedAt = now()->subDays(fake()->numberBetween(0, 20));

        return [
            'subscription_id' => $plan->id,
            'subscription_started_at' => $startedAt,
            'subscription_expires_at' => $startedAt->copy()->addDays($plan->duration_days),
        ];
    }
}
