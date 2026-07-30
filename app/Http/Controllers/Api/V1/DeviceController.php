<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\UserDevice;
use App\Services\Push\PushDriver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Devices
 *
 * Push-notification device registry. The app registers its provider token here so
 * the platform can reach it, and unregisters on sign-out.
 */
class DeviceController extends ApiController
{
    /**
     * Register this device
     *
     * Claims a push token for the authenticated user. Idempotent — call it on
     * every launch and after every token refresh. If the token was previously
     * registered to a different account (a shared device, or a reinstall), it is
     * re-pointed, so the previous owner stops receiving this device's push.
     *
     * @bodyParam token string required The provider (FCM) registration token. Example: fMEP0b2sTk...
     * @bodyParam platform string required ios or android. Example: android
     * @bodyParam locale string The app UI language (ar|fr|en). Example: ar
     * @bodyParam app_version string The client build, for diagnostics. Example: 1.4.2
     *
     * @response 204 {}
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:255'],
            'platform' => ['required', 'string', 'in:ios,android'],
            'locale' => ['nullable', 'string', 'in:ar,fr,en'],
            'app_version' => ['nullable', 'string', 'max:20'],
        ]);

        UserDevice::register(
            $request->user(),
            $data['token'],
            $data['platform'],
            $data['locale'] ?? null,
            $data['app_version'] ?? null,
        );

        return $this->noContent();
    }

    /**
     * Unregister this device
     *
     * Removes the token so this device stops receiving push. Call it on sign-out.
     * Only the owner's own registration is removed — passing someone else's token
     * is a no-op. Idempotent: an unknown token also returns 204.
     *
     * @bodyParam token string required The token to remove. Example: fMEP0b2sTk...
     *
     * @response 204 {}
     */
    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:255'],
        ]);

        UserDevice::where('user_id', $request->user()->id)
            ->where('token', $data['token'])
            ->delete();

        return $this->noContent();
    }

    /**
     * Push status
     *
     * Whether the platform can actually deliver push right now, and how many
     * devices this user has registered. Lets the app tell "notifications are off
     * on this device" apart from "the server has no push provider configured".
     */
    public function status(Request $request): JsonResponse
    {
        return $this->ok([
            'push_enabled' => PushDriver::isEnabled(),
            'devices' => UserDevice::where('user_id', $request->user()->id)->count(),
        ]);
    }
}
