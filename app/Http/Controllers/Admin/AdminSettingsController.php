<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Super-Admin editing of runtime platform parameters (spec §8.2). Gated by
 * 'system.parameters.manage'. Values are stored in system_settings and read
 * back everywhere via the setting() helper.
 */
class AdminSettingsController extends Controller
{
    public function index(): View
    {
        $this->authorize('system.parameters.manage');

        $settings = SystemSetting::orderBy('group')->orderBy('key')->get()->groupBy('group');

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('system.parameters.manage');

        $posted = (array) $request->input('settings', []);

        foreach (SystemSetting::all() as $setting) {
            if ($setting->type === 'bool') {
                // Unchecked checkboxes are absent from the payload → store false.
                $value = $request->boolean("settings.{$setting->key}") ? '1' : '0';
            } else {
                if (! array_key_exists($setting->key, $posted)) {
                    continue;
                }
                $value = (string) $posted[$setting->key];
            }

            $setting->update(['value' => $value, 'updated_by' => auth()->id()]);
        }

        Cache::forget('system_settings');

        AuditLog::log('SETTINGS_UPDATED', 'System', null);

        return back()->with('success', __('admin.settings.flash_saved'));
    }
}
