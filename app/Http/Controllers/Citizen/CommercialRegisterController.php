<?php

namespace App\Http\Controllers\Citizen;

use App\Enums\CommercialRegisterStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitCommercialRegisterRequest;
use App\Models\AuditLog;
use App\Models\CommercialRegister;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CommercialRegisterController extends Controller
{
    /** Public upload "type" slug → the column it fills. */
    private const DOCUMENT_FIELDS = [
        'register' => 'register_document_path',
        'tax-card' => 'tax_card_document_path',
    ];

    public function index(): View
    {
        $register = auth()->user()->commercialRegister;

        return view('citizen.commercial-register', compact('register'));
    }

    public function store(SubmitCommercialRegisterRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->safe()->only([
            'company_name', 'register_number', 'tax_number', 'activity_type', 'expiry_date',
        ]);

        // Newly uploaded scans replace the old ones; otherwise the stored path is
        // preserved (a rejected user who only fixes a text field need not re-upload).
        foreach (self::DOCUMENT_FIELDS as $type => $column) {
            $input = str_replace('-', '_', $type).'_document'; // register_document | tax_card_document
            if ($request->hasFile($input)) {
                $data[$column] = $request->file($input)->store('commercial-registers/'.$user->id, 'local');
            }
        }

        $data['status'] = CommercialRegisterStatus::PENDING;
        $data['submitted_at'] = now();
        $data['rejection_reason'] = null;
        $data['reviewed_at'] = null;
        $data['reviewed_by'] = null;

        $register = CommercialRegister::updateOrCreate(['user_id' => $user->id], $data);

        AuditLog::log('COMMERCIAL_REGISTER_SUBMITTED', 'CommercialRegister', $register->id, $user->id);

        return back()->with('success', __('commercial-register.submitted_success'));
    }

    /**
     * Stream one of the signed-in user's own scans. The files live on the private
     * disk, so this gated route is the only way to view them — and a user can
     * only ever reach their own.
     */
    public function document(string $type): StreamedResponse
    {
        abort_unless(array_key_exists($type, self::DOCUMENT_FIELDS), 404);

        $register = auth()->user()->commercialRegister;
        $path = $register?->{self::DOCUMENT_FIELDS[$type]};

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }
}
