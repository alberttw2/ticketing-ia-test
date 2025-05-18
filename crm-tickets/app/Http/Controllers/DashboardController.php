<?php

namespace App\Http\Controllers;

use App\Models\Establishment;
use App\Models\Ticket;
use App\Models\Product;
use App\Models\TicketItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Count of tickets by status
        $ticketsByStatus = Ticket::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();
            
        // Top 5 establishments by ticket count
        $topEstablishments = Establishment::withCount('tickets')
            ->orderByDesc('tickets_count')
            ->limit(5)
            ->get();
            
        // Top 5 products by appearance count
        $topProducts = Product::withCount('ticketItems')
            ->orderByDesc('ticket_items_count')
            ->limit(5)
            ->get();
            
        // Monthly ticket count for the past 6 months
        $monthlyCounts = Ticket::select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();
        
        // Recent tickets
        $recentTickets = Ticket::with('establishment')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
            
        // Counts of review tickets by establishment
        $reviewByEstablishment = Ticket::where('status', Ticket::STATUS_REVIEW)
            ->select('establishment_id', DB::raw('count(*) as count'))
            ->groupBy('establishment_id')
            ->with('establishment')
            ->get();
            
        // Total statistics
        $totalStats = [
            'establishments' => Establishment::count(),
            'products' => Product::count(),
            'tickets' => Ticket::count(),
            'items' => TicketItem::count(),
        ];
        
        return view('dashboard', compact(
            'ticketsByStatus',
            'topEstablishments',
            'topProducts',
            'monthlyCounts',
            'recentTickets',
            'reviewByEstablishment',
            'totalStats'
        ));
    }
}
