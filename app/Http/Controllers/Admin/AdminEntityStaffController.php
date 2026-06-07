<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Enums\KycStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Entity;
use App\Models\EntityUser;
use App\Models\User;
use App\Rules\AlgerianPhone;
use App\Rules\NinValidation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Entity-staff management (spec §8.3). Gated by 'entities.members.manage'
 * (SUPER_ADMIN — any entity; ENTITY_HEAD — locked to their own entity).
 *
 * A staff member is a real User (role + entity_id) so they reuse the single
 * web guard, 2FA, lockout and password-reset flows. The EntityUser row mirrors
 * the membership for this management surface.
 */
class AdminEntityStaffController extends Controller
{
    /** Roles that may be assigned to entity staff (SUPER_ADMIN is platform-only). */
    private const ASSIGNABLE_ROLES = [
        UserRole::ENTITY_HEAD,
        UserRole::CONTENT_ADMIN,
        UserRole::APPRAISER,
        UserRole::HUISSIER,
        UserRole::COMMITTEE_MEMBER,
    ];

    public function index(): View
    {
        $this->authorize('entities.members.manage');

        $query = EntityUser::with(['entity', 'user'])->latest();

        // An entity head only ever sees their own entity's staff.
        if (! $this->actorIsSuperAdmin()) {
            $query->where('entity_id', auth()->user()->entity_id);
        }

        $staff = $query->paginate(20);

        return view('admin.entity-staff.index', compact('staff'));
    }

    public function create(): View
    {
        $this->authorize('entities.members.manage');

        return view('admin.entity-staff.create', [
            'entities' => $this->actorIsSuperAdmin() ? Entity::orderBy('name')->get() : null,
            'roles' => self::ASSIGNABLE_ROLES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('entities.members.manage');

        $superAdmin = $this->actorIsSuperAdmin();

        $validated = $request->validate([
            'entity_id' => [$superAdmin ? 'required' : 'nullable', 'exists:entities,id'],
            'nin' => ['required', 'string', new NinValidation, 'unique:users,nin'],
            'first_name_ar' => ['required', 'string', 'max:100'],
            'last_name_ar' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', new AlgerianPhone, 'unique:users,phone'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'username' => ['required', 'string', 'max:50', 'unique:entity_users,username'],
            'role' => ['required', Rule::in($this->assignableRoleValues())],
            'birth_date' => ['required', 'date', 'before:'.now()->subYears(18)->toDateString()],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        $entityId = $superAdmin ? $validated['entity_id'] : auth()->user()->entity_id;
        abort_unless($entityId, 422);

        DB::transaction(function () use ($validated, $entityId) {
            $user = User::create([
                'nin' => $validated['nin'],
                'first_name_ar' => $validated['first_name_ar'],
                'last_name_ar' => $validated['last_name_ar'],
                'phone' => $validated['phone'],
                'email' => $validated['email'],
                'birth_date' => $validated['birth_date'],
                'password' => $validated['password'],
                'role' => $validated['role'],
                'entity_id' => $entityId,
                // Staff are provisioned by an admin — no public KYC/OTP gate.
                'kyc_status' => KycStatus::COMPLETE,
                'kyc_completed_at' => now(),
                'account_status' => AccountStatus::ACTIVE,
                'email_verified' => true,
            ]);

            $user->syncRoles([$validated['role']]);

            EntityUser::create([
                'entity_id' => $entityId,
                'user_id' => $user->id,
                'username' => $validated['username'],
                'full_name' => $user->fullNameAr(),
                'role' => $validated['role'],
                'is_active' => true,
            ]);

            AuditLog::log('ENTITY_STAFF_CREATED', 'User', $user->id, null, null, [
                'entity_id' => $entityId,
                'role' => $validated['role'],
            ]);
        });

        return redirect()->route('admin.entity-staff.index')
            ->with('success', __('admin.entity_staff.flash_created'));
    }

    public function edit(EntityUser $entityStaff): View
    {
        $this->authorize('entities.members.manage');
        $this->guardEntity($entityStaff);

        return view('admin.entity-staff.edit', [
            'member' => $entityStaff->load(['entity', 'user']),
            'roles' => self::ASSIGNABLE_ROLES,
        ]);
    }

    public function update(Request $request, EntityUser $entityStaff): RedirectResponse
    {
        $this->authorize('entities.members.manage');
        $this->guardEntity($entityStaff);

        $validated = $request->validate([
            'role' => ['required', Rule::in($this->assignableRoleValues())],
        ]);

        $entityStaff->update(['role' => $validated['role']]);

        if ($entityStaff->user) {
            $entityStaff->user->update(['role' => $validated['role']]);
            $entityStaff->user->syncRoles([$validated['role']]);
        }

        AuditLog::log('ENTITY_STAFF_UPDATED', 'EntityUser', $entityStaff->id, null, null, [
            'role' => $validated['role'],
        ]);

        return redirect()->route('admin.entity-staff.index')
            ->with('success', __('admin.entity_staff.flash_updated'));
    }

    public function deactivate(EntityUser $entityStaff): RedirectResponse
    {
        $this->authorize('entities.members.manage');
        $this->guardEntity($entityStaff);

        $reactivating = ! $entityStaff->is_active;
        $entityStaff->update(['is_active' => $reactivating]);

        // Mirror the membership state onto the login account so a deactivated
        // staff member loses access (and a reactivated one regains it).
        if ($entityStaff->user) {
            $entityStaff->user->update([
                'account_status' => $reactivating ? AccountStatus::ACTIVE : AccountStatus::SUSPENDED,
            ]);

            if (! $reactivating) {
                invalidate_user_sessions($entityStaff->user->id);
            }
        }

        AuditLog::log($reactivating ? 'ENTITY_STAFF_REACTIVATED' : 'ENTITY_STAFF_DEACTIVATED', 'EntityUser', $entityStaff->id);

        return back()->with('success', __('admin.entity_staff.flash_status_changed'));
    }

    private function actorIsSuperAdmin(): bool
    {
        return auth()->user()->hasRole(UserRole::SUPER_ADMIN->value);
    }

    /** An entity head may only touch staff inside their own entity. */
    private function guardEntity(EntityUser $entityStaff): void
    {
        if ($this->actorIsSuperAdmin()) {
            return;
        }

        abort_unless($entityStaff->entity_id === auth()->user()->entity_id, 403);
    }

    private function assignableRoleValues(): array
    {
        return array_map(fn (UserRole $r) => $r->value, self::ASSIGNABLE_ROLES);
    }
}
