<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
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
        $settings = SystemSetting::all();

        // Setting keys contain dots ("bidding.max_per_minute"), so the payload
        // must be read off the raw array — $request->input('settings.x') /
        // ->boolean('settings.x') would resolve the dot as nesting and silently
        // return null/false for EVERY key.
        $this->validateValues($settings, $posted);

        $changed = [];

        foreach ($settings as $setting) {
            if ($setting->type === 'bool') {
                // Unchecked checkboxes are absent from the payload → store false.
                $value = filter_var($posted[$setting->key] ?? false, FILTER_VALIDATE_BOOL) ? '1' : '0';
            } else {
                if (! array_key_exists($setting->key, $posted)) {
                    continue;
                }
                $value = trim((string) $posted[$setting->key]);
            }

            if ((string) $setting->value === $value) {
                continue;
            }

            $changed[$setting->key] = ['from' => $setting->value, 'to' => $value];
            $setting->update(['value' => $value, 'updated_by' => auth()->id()]);
        }

        Cache::forget('system_settings');

        // Platform-wide action — no single resource row, hence the null id.
        AuditLog::log('SETTINGS_UPDATED', 'System', null, null, null, ['changed' => $changed]);

        return back()->with('success', __('admin.settings.flash_saved'));
    }

    /**
     * Type-aware validation of the posted values. Without it a typo in a numeric
     * field was cast to 0 and silently applied to fee/security maths.
     *
     * @param  \Illuminate\Support\Collection<int, SystemSetting>  $settings
     * @param  array<string, mixed>  $posted
     */
    private function validateValues($settings, array $posted): void
    {
        $rules = [];
        $attributes = [];

        foreach ($settings as $setting) {
            if ($setting->type === 'bool' || ! array_key_exists($setting->key, $posted)) {
                continue;
            }

            // Rules are keyed with the literal (dotted) key escaped so the
            // validator treats it as one segment instead of a nested path.
            $field = 'settings.'.str_replace('.', '\.', $setting->key);

            $rules[$field] = match ($setting->type) {
                'int' => ['required', 'integer', 'min:0'],
                'float' => ['required', 'numeric', 'min:0'],
                default => ['nullable', 'string', 'max:255'],
            };

            // Both spellings: the escaped key is what the rules use, the plain
            // one is what the message builder looks up once placeholders are
            // resolved back to literal dots.
            $label = __('admin.settings.key_'.str_replace('.', '_', $setting->key));
            $attributes[$field] = $label;
            $attributes['settings.'.$setting->key] = $label;
        }

        Validator::make(['settings' => $posted], $rules, [], $attributes)->validate();
    }
}
