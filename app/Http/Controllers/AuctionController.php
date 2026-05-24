<?php

namespace App\Http\Controllers;

use App\Models\Auction;
use App\Models\AuctionParticipant;
use App\Models\AuditLog;
use App\Models\Bid;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuctionController extends Controller
{
    public function index(Request $request): View
    {
        $query = Auction::public()->with(['entity', 'category', 'wilaya']);

        if ($request->filled('search')) {
            $query->where('title_ar', 'LIKE', '%' . $request->search . '%');
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('wilaya_id')) {
            $query->where('wilaya_id', $request->wilaya_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('auction_type')) {
            $query->where('auction_type', $request->auction_type);
        }

        $auctions = $query->latest('start_time')->paginate(12)->withQueryString();

        return view('auctions.index', compact('auctions'));
    }

    public function show(Auction $auction): View
    {
        $auction->load(['entity', 'category', 'wilaya']);

        $bids = $auction->bids()
            ->where('is_valid', true)
            ->latest('bid_time')
            ->limit(10)
            ->get()
            ->map(fn (Bid $bid) => [
                'alias' => $bid->bidderAlias(),
                'amount' => $bid->amount,
                'bid_time' => $bid->bid_time,
            ]);

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
