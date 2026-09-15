<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PublicationArea;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\PublicationPackage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Publication packages / display spaces (client edit 16): the admin defines the
 * display areas, their normal price and the priority surcharge. Gated by
 * 'publication.manage' (SUPER_ADMIN). Prices are entered in dinars.
 */
class AdminPublicationPackageController extends Controller
{
    public function index(): View
    {
        $this->authorize('publication.manage');

        $packages = PublicationPackage::withCount('auctions')->orderBy('sort_order')->orderBy('price')->get();

        return view('admin.publication-packages.index', compact('packages'));
    }

    public function create(): View
    {
        $this->authorize('publication.manage');

        return view('admin.publication-packages.create', ['package' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('publication.manage');

        $package = PublicationPackage::create($this->validated($request));

        AuditLog::log('PUBLICATION_PACKAGE_CREATED', 'PublicationPackage', (string) $package->id, null, null, ['code' => $package->code]);

        return redirect()->route('admin.publication-packages.index')->with('success', __('publication.flash_created'));
    }

    public function edit(PublicationPackage $publicationPackage): View
    {
        $this->authorize('publication.manage');

        return view('admin.publication-packages.edit', ['package' => $publicationPackage]);
    }

    public function update(Request $request, PublicationPackage $publicationPackage): RedirectResponse
    {
        $this->authorize('publication.manage');

        $publicationPackage->update($this->validated($request, $publicationPackage));

        AuditLog::log('PUBLICATION_PACKAGE_UPDATED', 'PublicationPackage', (string) $publicationPackage->id);

        return redirect()->route('admin.publication-packages.index')->with('success', __('publication.flash_updated'));
    }

    public function toggle(PublicationPackage $publicationPackage): RedirectResponse
    {
        $this->authorize('publication.manage');

        $publicationPackage->update(['is_active' => ! $publicationPackage->is_active]);

        AuditLog::log('PUBLICATION_PACKAGE_TOGGLED', 'PublicationPackage', (string) $publicationPackage->id, null, null, [
            'is_active' => $publicationPackage->is_active,
        ]);

        return back()->with('success', __('publication.flash_updated'));
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?PublicationPackage $package = null): array
    {
        $max = (int) config('mazayada.limits.max_price_dzd', 10_000_000_000);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:40', 'alpha_dash', Rule::unique('publication_packages', 'code')->ignore($package?->id)],
            'name_ar' => ['required', 'string', 'max:150'],
            'name_fr' => ['nullable', 'string', 'max:150'],
            'name_en' => ['nullable', 'string', 'max:150'],
            'description_ar' => ['nullable', 'string', 'max:1000'],
            'description_fr' => ['nullable', 'string', 'max:1000'],
            'description_en' => ['nullable', 'string', 'max:1000'],
            'display_area' => ['required', Rule::enum(PublicationArea::class)],
            'price' => ['required', 'numeric', 'min:0', 'max:'.$max],
            'priority_price' => ['required', 'numeric', 'min:0', 'max:'.$max],
            'duration_days' => ['required', 'integer', 'min:1', 'max:365'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ]);

        $data['price'] = (int) round($data['price'] * 100);
        $data['priority_price'] = (int) round($data['priority_price'] * 100);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');
        $data['code'] = strtoupper($data['code']);

        return $data;
    }
}
