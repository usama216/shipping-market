<?php

namespace App\Http\Controllers;

use App\Helpers\PackageStatus;
use App\Models\Package;
use App\Models\Ship;
use App\Models\Transaction;
use App\Models\User;
use App\Repositories\TransactionRepository;
use App\Repositories\UserRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    protected $transactionRepository, $userRepository;

    public function __construct(TransactionRepository $transactionRepository, UserRepository $userRepository)
    {
        $this->transactionRepository = $transactionRepository;
        $this->userRepository = $userRepository;
    }

    public function index(Request $request)
    {
        // Date range filtering
        $dateRange = $request->get('range', '30'); // Default 30 days
        $startDate = match ($dateRange) {
            'today' => Carbon::today(),
            '7' => Carbon::now()->subDays(7),
            '30' => Carbon::now()->subDays(30),
            '90' => Carbon::now()->subDays(90),
            default => Carbon::now()->subDays(30),
        };

        // Previous period for comparison
        $daysDiff = Carbon::now()->diffInDays($startDate);
        $previousStart = (clone $startDate)->subDays($daysDiff);
        $previousEnd = $startDate;

        // Current period stats
        $saleAmount = Transaction::where('created_at', '>=', $startDate)->sum('amount');
        $previousSaleAmount = Transaction::whereBetween('created_at', [$previousStart, $previousEnd])->sum('amount');

        $totalCustomers = User::where('type', 2)->where('is_active', 1)->count();
        $newCustomers = User::where('type', 2)->where('is_active', 1)->where('created_at', '>=', $startDate)->count();
        $previousNewCustomers = User::where('type', 2)->where('is_active', 1)->whereBetween('created_at', [$previousStart, $previousEnd])->count();

        // Package counts by status
        $packagesActionRequired = Package::where('status', PackageStatus::ACTION_REQUIRED)->count();
        $packagesInReview = Package::where('status', PackageStatus::IN_REVIEW)->count();
        $packagesReadyToSend = Package::where('status', PackageStatus::READY_TO_SEND)->count();
        $totalPackages = Package::count();

        // Shipment stats
        $activeShipments = Ship::where('status', '!=', 'delivered')->count();
        $totalShipments = Ship::count();
        $todayShipments = Ship::whereDate('created_at', Carbon::today())->count();

        // Recent activity
        $recentTransactions = Transaction::with('user')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'amount' => $t->amount,
                'customer' => $t->user?->first_name . ' ' . $t->user?->last_name,
                'date' => $t->created_at->format('M d, Y'),
                'time' => $t->created_at->format('h:i A'),
            ]);

        $recentCustomers = User::where('type', 2)
            ->where('is_active', 1)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->first_name . ' ' . $u->last_name,
                'email' => $u->email,
                'suite' => $u->suite,
                'joined' => $u->created_at->format('M d, Y'),
            ]);

        // Today's stats
        $todayTransactions = Transaction::whereDate('created_at', Carbon::today())->count();
        $todaySales = Transaction::whereDate('created_at', Carbon::today())->sum('amount');

        // Calculate trends (percentage change)
        $salesTrend = $previousSaleAmount > 0
            ? round((($saleAmount - $previousSaleAmount) / $previousSaleAmount) * 100, 1)
            : ($saleAmount > 0 ? 100 : 0);

        $customersTrend = $previousNewCustomers > 0
            ? round((($newCustomers - $previousNewCustomers) / $previousNewCustomers) * 100, 1)
            : ($newCustomers > 0 ? 100 : 0);

        return Inertia::render('Admin/Dashboard/Report', [
            'saleAmount' => $saleAmount,
            'totalCustomers' => $totalCustomers,
            'newCustomers' => $newCustomers,
            'salesTrend' => $salesTrend,
            'customersTrend' => $customersTrend,
            'packagesActionRequired' => $packagesActionRequired,
            'packagesInReview' => $packagesInReview,
            'packagesReadyToSend' => $packagesReadyToSend,
            'totalPackages' => $totalPackages,
            'activeShipments' => $activeShipments,
            'totalShipments' => $totalShipments,
            'todayShipments' => $todayShipments,
            'todayTransactions' => $todayTransactions,
            'todaySales' => $todaySales,
            'recentTransactions' => $recentTransactions,
            'recentCustomers' => $recentCustomers,
            'selectedRange' => $dateRange,
        ]);
    }
}
