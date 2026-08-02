<?php

namespace App\Http\Controllers;

use App\Models\Auction;
use App\Models\Bid;
use App\Models\Category;
use App\Models\Entity;
use App\Models\User;
use App\Models\Wilaya;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $auctions = Auction::public()
            ->with(['entity', 'category', 'wilaya'])
            ->withCount('bids')
            ->latest('start_time')
            ->limit(8)
            ->get();

        $categories = Category::where('is_active', true)
            ->withCount('auctions')
            ->get();

        $wilayas = Wilaya::orderBy('code')->get();

        // Every headline figure is a live count. The hero and the institutions
        // strip used to print hand-written marketing numbers ("+2400 auctions",
        // "+320" per body), which contradicted the platform's own transparency
        // promise the moment anyone counted the listings.
        $stats = [
            'total_users' => User::count(),
            'active_auctions' => Auction::active()->count(),
            'total_bids' => Bid::count(),
            'entities_count' => Entity::where('is_active', true)->count(),
            'wilayas_count' => Wilaya::count(),
            'public_auctions' => Auction::public()->count(),
        ];

        // Institutions strip — real bodies with their real published counts.
        $entities = Entity::where('is_active', true)
            ->withCount(['auctions as public_auctions_count' => fn ($q) => $q->public()])
            ->orderByDesc('public_auctions_count')
            ->limit(5)
            ->get();

        return view('home', compact('auctions', 'categories', 'wilayas', 'stats', 'entities'));
    }
}
