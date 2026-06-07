<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

/**
 * Gated access to private generated documents. SUPER_ADMIN is short-circuited by
 * the Gate::before hook in AuthServiceProvider.
 */
class DocumentPolicy
{
    public function download(User $user, Document $document): bool
    {
        // Public documents (e.g. a published condition book) are downloadable by
        // any authenticated user.
        if ($document->is_public) {
            return true;
        }

        // The owner (winner / purchaser) of the document.
        if ($document->user_id && $document->user_id === $user->id) {
            return true;
        }

        // The auction winner can always fetch their auction's documents.
        $document->loadMissing('auction');
        if ($document->auction && $document->auction->winner_user_id === $user->id) {
            return true;
        }

        // Staff of the owning entity (platform-wide staff are not restricted).
        if ($user->isStaff()) {
            return $user->entity_id === null
                || $user->entity_id === $document->auction?->entity_id;
        }

        return false;
    }
}
