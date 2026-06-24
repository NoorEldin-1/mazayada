<?php

namespace App\Services;

use App\Enums\AppealStatus;
use App\Models\Appeal;
use App\Models\Auction;
use App\Models\AuditLog;
use App\Models\User;
use DomainException;

/**
 * The single source of truth for the appeals (الطعون) state machine. Every
 * transition lives here, guards its own precondition, writes an audit entry and
 * fires the matching notification. Controllers authorize via AppealPolicy and
 * then delegate — but these guards also hold for SUPER_ADMIN, who bypasses the
 * policy through Gate::before, so an illegal transition can never slip through.
 *
 * Flow:  PENDING ──forward──▶ FORWARDED_TO_ENTITY ──entityDecide──▶ ENTITY_{APPROVED,REJECTED}
 *                                                                       │
 *           └────────────── rejectAtIntake ──▶ REJECTED                 │
 *                                                       confirm ◀───────┘
 *                                                          ▼
 *                                                  APPROVED / REJECTED
 */
class AppealService
{
    public function __construct(private NotificationService $notifications) {}

    /**
     * A citizen files an appeal against an auction result. Requires eligibility
     * (participant + valid bid + open window) and that no prior appeal exists.
     *
     * @throws DomainException
     */
    public function submit(User $user, Auction $auction, string $subject, string $reason): Appeal
    {
        if (! $auction->canBeAppealedBy($user)) {
            throw new DomainException(__('appeals.error_not_eligible'));
        }

        if ($auction->appeals()->where('user_id', $user->id)->exists()) {
            throw new DomainException(__('appeals.error_already_filed'));
        }

        $appeal = Appeal::create([
            'user_id' => $user->id,
            'auction_id' => $auction->id,
            'subject' => $subject,
            'reason' => $reason,
            'status' => AppealStatus::PENDING,
        ]);

        AuditLog::log('APPEAL_CREATED', 'Appeal', $appeal->id, null, null, [
            'auction_id' => $auction->id,
            'subject' => $subject,
        ]);

        $this->notifications->appealSubmitted($appeal);

        return $appeal;
    }

    /**
     * Platform admin forwards a pending appeal to the organising entity.
     *
     * @throws DomainException
     */
    public function forward(Appeal $appeal, User $admin): void
    {
        $this->assertStatus($appeal, [AppealStatus::PENDING]);

        $appeal->update([
            'status' => AppealStatus::FORWARDED_TO_ENTITY,
            'forwarded_by' => $admin->id,
            'forwarded_at' => now(),
        ]);

        AuditLog::log('APPEAL_FORWARDED', 'Appeal', $appeal->id);

        $this->notifications->appealForwarded($appeal->fresh());
    }

    /**
     * Platform admin rejects an obviously-invalid appeal at intake, skipping the
     * entity. Terminal.
     *
     * @throws DomainException
     */
    public function rejectAtIntake(Appeal $appeal, User $admin, string $response): void
    {
        $this->assertStatus($appeal, [AppealStatus::PENDING]);

        $appeal->update([
            'status' => AppealStatus::REJECTED,
            'admin_response' => $response,
            'resolved_by' => $admin->id,
            'resolved_at' => now(),
        ]);

        AuditLog::log('APPEAL_REJECTED_AT_INTAKE', 'Appeal', $appeal->id);

        $this->notifications->appealResolved($appeal->fresh());
    }

    /**
     * The organising entity approves or rejects a forwarded appeal. $decision is
     * the entity's verdict expressed as a terminal status (APPROVED | REJECTED);
     * it is recorded so a later admin override is detectable.
     *
     * @throws DomainException
     */
    public function entityDecide(Appeal $appeal, AppealStatus $decision, string $response): void
    {
        $this->assertStatus($appeal, [AppealStatus::FORWARDED_TO_ENTITY]);
        $this->assertVerdict($decision);

        $appeal->update([
            'status' => $decision === AppealStatus::APPROVED
                ? AppealStatus::ENTITY_APPROVED
                : AppealStatus::ENTITY_REJECTED,
            'entity_decision' => $decision,
            'entity_response' => $response,
            'entity_decided_at' => now(),
        ]);

        AuditLog::log('APPEAL_ENTITY_DECISION', 'Appeal', $appeal->id, null, null, [
            'decision' => $decision->value,
        ]);

        $this->notifications->appealEntityDecided($appeal->fresh());
    }

    /**
     * Platform admin confirms the final outcome. By default this honours the
     * entity's verdict, but the admin is the ultimate authority and may override
     * it — the override is flagged in the audit trail. Terminal.
     *
     * @throws DomainException
     */
    public function confirm(Appeal $appeal, User $admin, AppealStatus $finalDecision, string $response): void
    {
        $this->assertStatus($appeal, [AppealStatus::ENTITY_APPROVED, AppealStatus::ENTITY_REJECTED]);
        $this->assertVerdict($finalDecision);

        $overrode = $appeal->entity_decision !== null && $appeal->entity_decision !== $finalDecision;

        $appeal->update([
            'status' => $finalDecision,
            'admin_response' => $response,
            'resolved_by' => $admin->id,
            'resolved_at' => now(),
        ]);

        AuditLog::log('APPEAL_RESOLVED', 'Appeal', $appeal->id, null, null, [
            'decision' => $finalDecision->value,
            'entity_decision' => $appeal->entity_decision?->value,
            'overrode_entity' => $overrode,
        ]);

        $this->notifications->appealResolved($appeal->fresh());
    }

    /**
     * @param  array<int, AppealStatus>  $allowed
     *
     * @throws DomainException
     */
    private function assertStatus(Appeal $appeal, array $allowed): void
    {
        if (! in_array($appeal->status, $allowed, true)) {
            throw new DomainException(__('appeals.error_invalid_transition'));
        }
    }

    /** @throws DomainException */
    private function assertVerdict(AppealStatus $decision): void
    {
        if (! in_array($decision, [AppealStatus::APPROVED, AppealStatus::REJECTED], true)) {
            throw new DomainException(__('appeals.error_invalid_decision'));
        }
    }
}
