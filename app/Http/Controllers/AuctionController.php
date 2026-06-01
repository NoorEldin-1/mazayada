<?php

namespace App\Http\Controllers;

use App\Models\Auction;
use App\Models\AuctionParticipant;
use App\Models\AuditLog;
use App\Models\Bid;
use App\Models\Category;
use App\Models\Wilaya;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuctionController extends Controller
{
    public function index(Request $request): View
    {
        $query = Auction::public()->with(['entity', 'category', 'wilaya']);

        if ($request->filled('q')) {
            $query->where('title_ar', 'LIKE', '%' . $request->q . '%');
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->filled('wilaya')) {
            $query->where('wilaya_id', $request->wilaya);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('auction_type', $request->type);
        }

        $auctions = $query->latest('start_time')->paginate(12)->withQueryString();

        $categories = Category::where('is_active', true)->get();
        $wilayas = Wilaya::all();

        return view('auctions.index', compact('auctions', 'categories', 'wilayas'));
    }

    public function show(Auction $auction): View
    {
        $auction->load(['entity', 'category', 'wilaya']);

        $bids = $auction->bids()
            ->where('is_valid', true)
            ->latest('bid_time')
            ->limit(10)
            ->get();

        $isParticipant = false;
        if (auth()->check()) {
            $isParticipant = AuctionParticipant::where('auction_id', $auction->id)
                ->where('user_id', auth()->id())
                ->exists();
        }

        return view('auctions.show', compact('auction', 'bids', 'isParticipant'));
    }

    public function registerParticipant(Auction $auction): RedirectResponse
    {
        $existing = AuctionParticipant::where('auction_id', $auction->id)
            ->where('user_id', auth()->id())
            ->exists();

        if ($existing) {
            return back()->with('info', 'أنت مسجل بالفعل في هذا المزاد.');
        }

        AuctionParticipant::create([
            'auction_id' => $auction->id,
            'user_id' => auth()->id(),
            'deposit_paid' => true,
            'registered_at' => now(),
        ]);

        AuditLog::log('PARTICIPANT_REGISTERED', 'Auction', $auction->id, null, null, [
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', 'تم التسجيل في المزاد بنجاح.');
    }

    public function bid(Request $request, Auction $auction): RedirectResponse|JsonResponse
    {
        $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
        ]);

        $currentPrice = $auction->currentPrice();

        if ($request->amount <= $currentPrice) {
            $error = 'يجب أن يكون المبلغ أعلى من السعر الحالي (' . $auction->formatPrice($currentPrice) . ').';

            if ($request->wantsJson()) {
                return response()->json(['error' => $error], 422);
            }

            return back()->withErrors(['amount' => $error]);
        }

        $bid = Bid::create([
            'auction_id' => $auction->id,
            'user_id' => auth()->id(),
            'amount' => $request->amount,
            'bid_time' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'is_valid' => true,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'bid' => [
                    'amount' => $bid->amount,
                    'bid_time' => $bid->bid_time,
                    'alias' => $bid->bidderAlias(),
                ],
            ]);
        }

        return back()->with('success', 'تم تقديم عرضك بنجاح.');
    }
}
