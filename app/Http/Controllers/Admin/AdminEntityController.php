<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Enums\AccountType;
use App\Enums\EntityType;
use App\Enums\KycStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Entity;
use App\Models\User;
use App\Models\Wilaya;
use App\Rules\AlgerianPhone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
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
        $credentials = $this->validateCredentials($request, creating: true);

        // The entity and its institutional login are created together so an
        // entity always has exactly one read-only account from the start.
        $entity = DB::transaction(function () use ($data, $credentials) {
            $entity = Entity::create($data);
            $this->provisionAccount($entity, $credentials['password']);

            return $entity;
        });

        AuditLog::log('ENTITY_CREATED', 'Entity', $entity->id, null, null, [
            'name' => $entity->getRawOriginal('name'),
        ]);

        return redirect()->route('admin.entities.index')
            ->with('success', __('admin.entities.flash_created'));
    }

    public function show(Entity $entity): View
    {
        $this->authorize('entities.manage');

        $entity->load(['wilaya', 'commune', 'account'])
            ->loadCount(['entityUsers', 'auctions']);

        return view('admin.entities.show', compact('entity'));
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

        $data = $this->validateData($request, $entity);
        $credentials = $this->validateCredentials($request, creating: false);

        DB::transaction(function () use ($entity, $data, $credentials) {
            $entity->update($data);
            $this->syncAccount($entity, $credentials['password']);
        });

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

    private function validateData(Request $request, ?Entity $entity = null): array
    {
        // The email is the entity's own login identity, so it must be present
        // and unique across all accounts (ignoring the entity's own account on edit).
        $accountId = $entity?->account()->value('id');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_fr' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::enum(EntityType::class)],
            'wilaya_id' => ['required', 'exists:wilayas,id'],
            'commune_id' => ['nullable', Rule::exists('communes', 'id')->where('wilaya_id', $request->input('wilaya_id'))],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', new AlgerianPhone],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($accountId)],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }

    /**
     * The entity login password. Required on create; optional on edit (a blank
     * value leaves the existing password untouched).
     *
     * @return array{password: ?string}
     */
    private function validateCredentials(Request $request, bool $creating): array
    {
        $validated = $request->validate([
            'password' => [$creating ? 'required' : 'nullable', 'string', 'confirmed', Password::defaults()],
        ]);

        return ['password' => filled($validated['password'] ?? null) ? $validated['password'] : null];
    }

    /**
     * Create the entity's institutional login: a read-only ENTITY_VIEWER account
     * (no person identity) scoped to the entity by EntityScope. Display name and
     * email mirror the entity record.
     */
    private function provisionAccount(Entity $entity, string $password): void
    {
        $user = User::create([
            'account_type' => AccountType::INSTITUTION,
            'role' => UserRole::ENTITY_VIEWER,
            'entity_id' => $entity->id,
            'email' => $entity->email,
            'password' => $password,
            // Display identity is the entity's own name (no person fields).
            'first_name_ar' => $entity->name_ar,
            'last_name_ar' => '',
            'first_name_fr' => $entity->name_fr,
            // Admin-provisioned — no public KYC/OTP gate.
            'kyc_status' => KycStatus::COMPLETE,
            'kyc_completed_at' => now(),
            'account_status' => $entity->is_active ? AccountStatus::ACTIVE : AccountStatus::SUSPENDED,
            'email_verified' => true,
        ]);

        $user->syncRoles([UserRole::ENTITY_VIEWER->value]);
    }

    /**
     * Keep the institutional account in step with the entity record: mirror the
     * email/display name, reflect the active flag onto the login, and optionally
     * reset the password. Back-fills the account for entities created before
     * logins existed (only when a password is supplied).
     */
    private function syncAccount(Entity $entity, ?string $password): void
    {
        $account = $entity->account()->first();

        if (! $account) {
            if ($password !== null) {
                $this->provisionAccount($entity, $password);
            }

            return;
        }

        $suspending = ! $entity->is_active && $account->account_status === AccountStatus::ACTIVE;

        $updates = [
            'email' => $entity->email,
            'first_name_ar' => $entity->name_ar,
            'first_name_fr' => $entity->name_fr,
            'account_status' => $entity->is_active ? AccountStatus::ACTIVE : AccountStatus::SUSPENDED,
        ];

        if ($password !== null) {
            $updates['password'] = $password;
        }

        $account->update($updates);

        // A suspended entity must lose any live admin sessions immediately.
        if ($suspending) {
            invalidate_user_sessions($account->id);
        }
    }
}
