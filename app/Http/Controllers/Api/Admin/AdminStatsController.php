<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use App\Models\JobOffer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\User;
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
                'orders' => Order::count(),
                'orders_by_status' => $this->countBy(Order::query(), 'status'),
            ],
            'real_estate' => [
                'properties' => Property::count(),
                'reservations' => Reservation::count(),
                'reservations_by_status' => $this->countBy(Reservation::query(), 'status'),
            ],
            'jobs' => [
                'job_offers' => JobOffer::count(),
                'applications' => JobApplication::count(),
                'applications_by_status' => $this->countBy(JobApplication::query(), 'status'),
            ],
            'payments' => [
                'revenue' => (float) Payment::where('status', 'success')->sum('amount'),
                'revenue_this_month' => (float) Payment::where('status', 'success')
                    ->where('created_at', '>=', $monthStart)
                    ->sum('amount'),
                'pending' => Payment::where('status', 'pending')->count(),
                'by_status' => $this->countBy(Payment::query(), 'status'),
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
