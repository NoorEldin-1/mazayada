<?php

namespace App\Http\Controllers\Citizen;

use App\Enums\KycStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitKycRequest;
use App\Models\AuditLog;
use App\Models\UserBiometric;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KycController extends Controller
{
    /** Map of the public upload "type" slug to the biometrics column it fills. */
    private const DOCUMENT_FIELDS = [
        'id-front' => 'id_front_path',
        'id-back' => 'id_back_path',
        'selfie-with-id' => 'selfie_with_id_path',
    ];

    public function index(): View
    {
        $user = auth()->user()->load('biometrics', 'commune');

        return view('citizen.kyc', compact('user'));
    }

    public function upload(Request $request, string $type): RedirectResponse
    {
        if (! array_key_exists($type, self::DOCUMENT_FIELDS)) {
            return back()->withErrors(['file' => __('kyc.file_type_not_allowed')]);
        }

        // No edits once submitted/approved — only PENDING or REJECTED can upload.
        if (! auth()->user()->kycCanSubmit()) {
            return back()->withErrors(['file' => __('kyc.locked')]);
        }

        $request->validate([
            // Stored privately (Law 18-07); 2 MB cap, JPG/PNG only.
            'file' => ['required', 'image', 'mimes:jpeg,png', 'max:2048'],
        ]);

        // Private disk — never reachable by a static /storage URL. Served only
        // through the gated document() route below.
        $path = $request->file('file')->store('kyc/'.auth()->id(), 'local');

        UserBiometric::updateOrCreate(
            ['user_id' => auth()->id()],
            [self::DOCUMENT_FIELDS[$type] => $path]
        );

        return back()->with('success', __('kyc.file_uploaded'));
    }

    public function submit(SubmitKycRequest $request): RedirectResponse
    {
        $user = auth()->user();

        // All three identity documents must be present before a request can go
        // to review — the FormRequest already gated PENDING/REJECTED status.
        if (! $user->hasAllKycDocuments()) {
            return back()
                ->withInput()
                ->withErrors(['file' => __('kyc.error_docs_required')]);
        }

        $user->update([
            ...$request->safe()->only([
                'first_name_fr', 'last_name_fr', 'father_name', 'mother_fullname',
                'profession', 'address', 'commune_id', 'postal_code', 'rip', 'expected_income',
            ]),
            'kyc_status' => KycStatus::UNDER_REVIEW,
            'kyc_submitted_at' => now(),
            'kyc_rejection_reason' => null,
        ]);

        AuditLog::log('KYC_SUBMITTED', 'User', $user->id, $user->id);

        return back()->with('success', __('kyc.submitted_success'));
    }

    /**
     * Stream one of the signed-in user's own KYC documents. The files live on
     * the private disk, so this is the only way to view them — and a user can
     * only ever reach their own.
     */
    public function document(string $type): StreamedResponse
    {
        abort_unless(array_key_exists($type, self::DOCUMENT_FIELDS), 404);

        $bio = auth()->user()->biometrics;
        $path = $bio?->{self::DOCUMENT_FIELDS[$type]};

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }
}
