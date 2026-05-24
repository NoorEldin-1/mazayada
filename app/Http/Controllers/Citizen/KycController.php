<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Models\UserBiometric;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KycController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $biometrics = $user->biometrics;

        return view('citizen.kyc', compact('user', 'biometrics'));
    }

    public function upload(Request $request, string $type): RedirectResponse
    {
        $allowedTypes = ['id-front', 'id-back', 'selfie', 'selfie-with-id'];

        if (!in_array($type, $allowedTypes)) {
            return back()->withErrors(['type' => 'نوع الملف غير مسموح.']);
        }

        $request->validate([
            'file' => ['required', 'image', 'max:5120'],
        ]);

        $path = $request->file('file')->store('uploads/kyc/' . auth()->id(), 'public');

        $fieldMap = [
            'id-front' => 'id_front_path',
            'id-back' => 'id_back_path',
            'selfie' => 'photo_biometric_path',
            'selfie-with-id' => 'selfie_with_id_path',
        ];

        UserBiometric::updateOrCreate(
            ['user_id' => auth()->id()],
            [$fieldMap[$type] => $path]
        );

        return back()->with('success', 'تم رفع الملف بنجاح.');
    }

    public function submit(Request $request): RedirectResponse
    {
        $request->validate([
            'first_name_fr' => ['required', 'string', 'max:100'],
            'last_name_fr' => ['required', 'string', 'max:100'],
            'father_name' => ['required', 'string', 'max:100'],
            'mother_fullname' => ['required', 'string', 'max:200'],
            'profession' => ['sometimes', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:255'],
            'commune_id' => ['required', 'exists:communes,id'],
            'postal_code' => ['required', 'string', 'max:5'],
            'rip' => ['sometimes', 'string', 'max:30'],
            'expected_income' => ['sometimes', 'string', 'max:50'],
        ]);

        auth()->user()->update($request->only([
            'first_name_fr', 'last_name_fr', 'father_name', 'mother_fullname',
            'profession', 'address', 'commune_id', 'postal_code', 'rip', 'expected_income',
        ]));

        return back()->with('success', 'تم حفظ المعلومات الشخصية بنجاح.');
    }
}
