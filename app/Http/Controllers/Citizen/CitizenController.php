<?php

namespace App\Http\Controllers\Citizen;

use App\Enums\AuctionStatus;
use App\Http\Controllers\Controller;
use App\Models\AuctionParticipant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CitizenController extends Controller
{
    public function dashboard(): View
    {
        $user = auth()->user();

        // Stat tiles — cheap COUNT(*) aggregates.
        $activeCount = $user->participations()
            ->whereHas('auction', fn ($q) => $q->active())
            ->count();

        $wonCount = $user->wonAuctions()->count();

        $totalParticipations = $user->participations()->count();

        $kycStatus = $user->kyc_status;

        // Recent won auctions — iterated by the view, must be a Collection.
        $wonAuctions = $user->wonAuctions()
            ->with(['category', 'wilaya'])
            ->latest('updated_at')
            ->limit(5)
            ->get();

        $recentNotifications = $user->userNotifications()
            ->latest('created_at')
            ->limit(10)
            ->get();

        return view('citizen.dashboard', compact(
            'activeCount', 'wonCount', 'totalParticipations',
            'kycStatus', 'wonAuctions', 'recentNotifications'
        ));
    }

    public function myAuctions(): View
    {
        $user = auth()->user();

        $participations = $user->participations()->with(['auction.category', 'auction.wilaya'])->get();

        $grouped = [
            'active' => $participations->filter(fn ($p) => $p->auction->isLive()),
            'won' => $participations->filter(fn ($p) => $p->auction->winner_user_id === $user->id),
            'lost' => $participations->filter(
                fn ($p) => $p->auction->status === AuctionStatus::CLOSED
                    && $p->auction->winner_user_id !== $user->id
            ),
            'upcoming' => $participations->filter(fn ($p) => $p->auction->status === AuctionStatus::PUBLISHED),
        ];

        return view('citizen.my-auctions', compact('grouped'));
    }

    public function notifications(): View
    {
        $notifications = auth()->user()->userNotifications()
            ->latest('created_at')
            ->paginate(20);

        return view('citizen.notifications', compact('notifications'));
    }

    public function profile(): View
    {
        $user = auth()->user();

        return view('citizen.profile', compact('user'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $request->validate([
            'phone' => ['sometimes', 'string', 'max:10'],
            'email' => ['sometimes', 'email', 'unique:users,email,' . auth()->id()],
            'address' => ['sometimes', 'string', 'max:255'],
            'commune_id' => ['sometimes', 'exists:communes,id'],
            'postal_code' => ['sometimes', 'string', 'max:5'],
            'profession' => ['sometimes', 'string', 'max:100'],
        ]);

        auth()->user()->update($request->only([
            'phone', 'email', 'address', 'commune_id', 'postal_code', 'profession',
        ]));

        return back()->with('success', 'تم تحديث الملف الشخصي بنجاح.');
    }
}
