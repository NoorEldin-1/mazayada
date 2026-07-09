<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CommercialRegisterStatus;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\SubmitCommercialRegisterRequest;
use App\Models\AuditLog;
use App\Models\CommercialRegister;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @group Commercial Register
 *
 * The user's Commercial Register (السجل التجاري) submission. Mirrors the web flow:
 * submit the register data plus two scans, an admin reviews it, and only a valid
 * (APPROVED and un-expired) register unlocks participation in auctions flagged
 * `requires_commerce_register`. Independent of KYC — a user may hold either, both
 * or neither.
 */
class CommercialRegisterController extends ApiController
{
    /** Public upload "type" slug → the column that stores the scan. */
    private const DOCUMENT_FIELDS = [
        'register' => 'register_document_path',
        'tax-card' => 'tax_card_document_path',
    ];

    /**
     * Register status
     *
     * The user's Commercial Register state, the data on file, which scans are
     * present, and whether a (re)submit is currently allowed. `status` is null
     * when nothing has been submitted yet.
     */
    public function show(Request $request): JsonResponse
    {
        $register = $request->user()->commercialRegister;

        return $this->ok([
            'status' => $register?->status?->value,
            'company_name' => $register?->company_name,
            'register_number' => $register?->register_number,
            'tax_number' => $register?->tax_number,
            'activity_type' => $register?->activity_type,
            'expiry_date' => $register?->expiry_date?->toDateString(),
            'rejection_reason' => $register?->rejection_reason,
            'submitted_at' => $register?->submitted_at?->toIso8601String(),
            'reviewed_at' => $register?->reviewed_at?->toIso8601String(),
            'can_submit' => $register === null || $register->canSubmit(),
            'is_valid' => $register?->isValid() ?? false,
            'is_expired' => $register?->isExpired() ?? false,
            'documents' => [
                'register' => (bool) $register?->register_document_path,
                'tax-card' => (bool) $register?->tax_card_document_path,
            ],
        ]);
    }

    /**
     * Submit the register
     *
     * Creates or updates the user's Commercial Register (multipart) and moves it to
     * PENDING for review. On a resubmission the previously uploaded scans are kept
     * unless replaced, so a rejected user who only fixes a text field need not
     * re-upload. A PENDING-under-review or APPROVED record is locked (403).
     *
     * @bodyParam company_name string required The company / trading entity name. Example: Sarl Mazayada
     * @bodyParam register_number string required The commercial register number. Example: 16/00-1234567 B 19
     * @bodyParam tax_number string required The tax identification number. Example: 000116001234567
     * @bodyParam activity_type string required The registered commercial activity. Example: تجارة السيارات
     * @bodyParam expiry_date string required The register expiry date (must be in the future). Example: 2030-12-31
     * @bodyParam register_document file The commercial register scan (PDF/JPG/PNG). Required on first submission.
     * @bodyParam tax_card_document file The tax card scan (PDF/JPG/PNG). Required on first submission.
     */
    public function store(SubmitCommercialRegisterRequest $request): JsonResponse
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

        return $this->ok(
            ['status' => CommercialRegisterStatus::PENDING->value],
            __('commercial-register.submitted_success'),
        );
    }

    /**
     * Download a scan
     *
     * Streams one of the authenticated user's own Commercial Register scans from
     * the private disk. Allowed types: register, tax-card. A user can never reach
     * another user's files.
     */
    public function document(Request $request, string $type): StreamedResponse
    {
        abort_unless(array_key_exists($type, self::DOCUMENT_FIELDS), 404);

        $path = $request->user()->commercialRegister?->{self::DOCUMENT_FIELDS[$type]};
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }
}
