<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EntityType;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Entity;
use App\Models\Wilaya;
use App\Rules\AlgerianPhone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Super-Admin management of government entities (spec §8.2). Gated by the
 * 'entities.manage' permission, which only SUPER_ADMIN holds.
 */
class AdminEntityController extends Controller
{
    public function index(): View
    {
        $this->authorize('entities.manage');

        $entities = Entity::with('wilaya')
            ->withCount(['entityUsers', 'auctions'])
            ->orderBy('name')
            ->paginate(20);

        return view('admin.entities.index', compact('entities'));
    }

    public function create(): View
    {
        $this->authorize('entities.manage');

        return view('admin.entities.create', [
            'wilayas' => Wilaya::orderBy('code')->get(),
            'types' => EntityType::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('entities.manage');

        $data = $this->validateData($request);
        $entity = Entity::create($data);

        AuditLog::log('ENTITY_CREATED', 'Entity', $entity->id, null, null, [
            'name' => $entity->getRawOriginal('name'),
        ]);

        return redirect()->route('admin.entities.index')
            ->with('success', __('admin.entities.flash_created'));
    }

    public function edit(Entity $entity): View
    {
        $this->authorize('entities.manage');

        return view('admin.entities.edit', [
            'entity' => $entity,
            'wilayas' => Wilaya::orderBy('code')->get(),
            'types' => EntityType::cases(),
        ]);
    }

    public function update(Request $request, Entity $entity): RedirectResponse
    {
        $this->authorize('entities.manage');

        $entity->update($this->validateData($request));

        AuditLog::log('ENTITY_UPDATED', 'Entity', $entity->id);

        return redirect()->route('admin.entities.index')
            ->with('success', __('admin.entities.flash_updated'));
    }

    public function destroy(Entity $entity): RedirectResponse
    {
        $this->authorize('entities.manage');

        if ($entity->auctions()->exists()) {
            return back()->withErrors(['delete' => __('admin.entities.cannot_delete_has_auctions')]);
        }

        $id = $entity->id;
        $entity->delete();

        AuditLog::log('ENTITY_DELETED', 'Entity', $id);

        return redirect()->route('admin.entities.index')
            ->with('success', __('admin.entities.flash_deleted'));
    }

    private function validateData(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_fr' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::enum(EntityType::class)],
            'wilaya_id' => ['required', 'exists:wilayas,id'],
            'commune_id' => ['nullable', Rule::exists('communes', 'id')->where('wilaya_id', $request->input('wilaya_id'))],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', new AlgerianPhone],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }
}
