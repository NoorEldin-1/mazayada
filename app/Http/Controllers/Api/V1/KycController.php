<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\IdDocumentType;
use App\Enums\KycStatus;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\KycUploadRequest;
use App\Http\Requests\SubmitKycRequest;
use App\Models\AuditLog;
use App\Models\UserBiometric;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @group KYC
 *
 * Identity verification: upload documents to the private disk, submit the profile
 * for review, and stream back the user's own documents. Mirrors the web KYC flow.
 */
class KycController extends ApiController
{
    /** Public upload "type" slug -> the biometrics column it fills. */
    private const DOCUMENT_FIELDS = [
        'id-front' => 'id_front_path',
        'id-back' => 'id_back_path',
        'selfie-with-id' => 'selfie_with_id_path',
        'photo-biometric' => 'photo_biometric_path',
    ];

    /**
     * KYC status
     *
     * The user's KYC state, which documents are on file, and whether a (re)submit
     * is currently allowed.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load('biometrics');
        $bio = $user->biometrics;

        return $this->ok([
            'status' => $user->kyc_status?->value,
            'submitted_at' => $user->kyc_submitted_at?->toIso8601String(),
            'completed_at' => $user->kyc_completed_at?->toIso8601String(),
            'rejection_reason' => $user->kyc_rejection_reason,
            'can_submit' => $user->kycCanSubmit(),
            'has_all_documents' => $user->hasAllKycDocuments(),
            'documents' => [
                'id-front' => (bool) $bio?->id_front_path,
                'id-back' => (bool) $bio?->id_back_path,
                'selfie-with-id' => (bool) $bio?->selfie_with_id_path,
                'photo-biometric' => (bool) $bio?->photo_biometric_path,
            ],
        ]);
    }

    /**
     * Upload a document
     *
     * Stores a KYC document (JPG/PNG) on the private disk. Allowed types:
     * id-front, id-back, selfie-with-id, photo-biometric.
     */
    public function upload(KycUploadRequest $request, string $type): JsonResponse
    {
        abort_unless(array_key_exists($type, self::DOCUMENT_FIELDS), 404);

        if (! $request->user()->kycCanSubmit()) {
            return $this->fail(__('kyc.locked'), [], 422);
        }

        $path = $request->file('file')->store('kyc/'.$request->user()->id, 'local');

        UserBiometric::updateOrCreate(
            ['user_id' => $request->user()->id],
            [self::DOCUMENT_FIELDS[$type] => $path],
        );

        return $this->ok(['type' => $type], __('kyc.file_uploaded'));
    }

    /**
     * Submit for review
     *
     * Validates the profile, requires all three identity documents, and moves the
     * account to UNDER_REVIEW.
     */
    public function submit(SubmitKycRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasAllKycDocuments()) {
            return $this->fail(__('kyc.error_docs_required'), [], 422);
        }

        $idColumn = [];
        if ($request->filled('id_type') && $request->filled('id_number')) {
            $column = IdDocumentType::from($request->safe()->input('id_type'))->column();
            $idColumn[$column] = $request->safe()->input('id_number');
        }

        $user->update([
            ...$request->safe()->only([
                'first_name_fr', 'last_name_fr', 'father_name', 'mother_name', 'mother_surname',
                'profession', 'address', 'commune_id', 'postal_code', 'rip', 'expected_income',
                'nif', 'nis',
            ]),
            ...$idColumn,
            'kyc_status' => KycStatus::UNDER_REVIEW,
            'kyc_submitted_at' => now(),
            'kyc_rejection_reason' => null,
        ]);

        AuditLog::log('KYC_SUBMITTED', 'User', $user->id, $user->id);

        return $this->ok(['status' => KycStatus::UNDER_REVIEW->value], __('kyc.submitted_success'));
    }

    /**
     * Download a KYC document
     *
     * Streams one of the authenticated user's own KYC documents from the private
     * disk (a user can never reach another user's files).
     */
    public function document(Request $request, string $type): StreamedResponse
    {
        abort_unless(array_key_exists($type, self::DOCUMENT_FIELDS), 404);

        $path = $request->user()->biometrics?->{self::DOCUMENT_FIELDS[$type]};
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }
}
