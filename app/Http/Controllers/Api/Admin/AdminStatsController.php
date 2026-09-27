<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\JobApplication;
use App\Models\JobOffer;
use App\Models\JobSeeker;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Property;
use App\Models\PropertyOwner;
use App\Models\Recruiter;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Wallet;
use App\Models\WithdrawalRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class AdminStatsController extends Controller
{
    /**
     * Aggregate platform-wide KPIs for the admin dashboard.
     */
    public function index(): JsonResponse
    {
        $monthStart = Carbon::now()->startOfMonth();

        return response()->json([
            'users' => [
                'total' => User::count(),
                'suspended' => User::where('status', 'suspended')->count(),
                'new_this_month' => User::where('created_at', '>=', $monthStart)->count(),
                'by_type' => $this->countBy(User::query(), 'user_type'),
            ],
            'commerce' => [
                'products' => Product::count(),
                'available_products' => Product::where('is_available', true)->count(),
                'orders' => Order::count(),
                'orders_this_month' => Order::where('created_at', '>=', $monthStart)->count(),
                'orders_by_status' => $this->countBy(Order::query(), 'status'),
                'vendors' => Vendor::count(),
                'drivers' => Driver::count(),
            ],
            'real_estate' => [
                'properties' => Property::count(),
                'available_properties' => Property::where('is_available', true)->count(),
                'reservations' => Reservation::count(),
                'reservations_by_status' => $this->countBy(Reservation::query(), 'status'),
                'owners' => PropertyOwner::count(),
            ],
            'jobs' => [
                'job_offers' => JobOffer::count(),
                'active_offers' => JobOffer::where('is_active', true)->count(),
                'applications' => JobApplication::count(),
                'applications_by_status' => $this->countBy(JobApplication::query(), 'status'),
                'recruiters' => Recruiter::count(),
                'job_seekers' => JobSeeker::count(),
            ],
            'payments' => [
                'revenue' => (float) Payment::where('status', 'success')->sum('amount'),
                'revenue_this_month' => (float) Payment::where('status', 'success')
                    ->where('created_at', '>=', $monthStart)
                    ->sum('amount'),
                'pending' => Payment::where('status', 'pending')->count(),
                'by_status' => $this->countBy(Payment::query(), 'status'),
            ],
            'wallets' => [
                'total_balance' => (float) Wallet::sum('balance'),
                'pending_withdrawals' => WithdrawalRequest::where('status', 'pending')->count(),
            ],
        ]);
    }

    /**
     * Return a { value => count } map grouped by a column.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<*>  $query
     * @return array<string, int>
     */
    private function countBy($query, string $column): array
    {
        return $query->selectRaw("{$column} as k, count(*) as c")
            ->groupBy($column)
            ->pluck('c', 'k')
            ->map(fn ($c) => (int) $c)
            ->all();
    }
}
