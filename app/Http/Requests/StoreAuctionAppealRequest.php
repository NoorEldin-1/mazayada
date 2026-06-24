<?php

namespace App\Http\Requests;

use App\Models\Auction;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Filing an appeal from the auction page. The route-bound auction must be
 * appealable by the current user (closed + within window + participant with a
 * valid bid); the one-appeal-per-auction rule is enforced in AppealService so it
 * can return a friendly message instead of a bare 403.
 */
class StoreAuctionAppealRequest extends FormRequest
{
    public function authorize(): bool
    {
        $auction = $this->route('auction');

        return $auction instanceof Auction
            && $this->user() !== null
            && $auction->canBeAppealedBy($this->user());
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }

    /**
     * On validation failure, send the user back to the appeals tab (the form is
     * hidden behind the tab control on the auction page) so the inline errors and
     * old input are visible without an extra click.
     */
    protected function getRedirectUrl(): string
    {
        return url()->previous().'#sec-appeals';
    }
}

