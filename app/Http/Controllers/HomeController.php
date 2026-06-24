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

        $stats = [
            'total_users' => User::count(),
            'active_auctions' => Auction::active()->count(),
            'total_bids' => Bid::count(),
            'entities_count' => Entity::where('is_active', true)->count(),
        ];

        return view('home', compact('auctions', 'categories', 'wilayas', 'stats'));
    }
}
