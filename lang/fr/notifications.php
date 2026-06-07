<?php

return [
    'title' => 'Notifications',
    'unread' => ':count non lue(s)',
    'empty' => 'Aucune notification',
    'mark_read' => 'Marquer comme lue',
    'mark_all_read' => 'Tout marquer comme lu',
    'flash_marked_read' => 'Notification marquée comme lue.',
    'flash_all_marked_read' => 'Toutes les notifications ont été marquées comme lues.',

    'events' => [
        'auction_won' => [
            'title' => 'Vous avez gagné l’enchère',
            'body' => 'Vous avez remporté « :auction » pour :amount. Payez sous :days jours.',
        ],
        'auction_lost' => [
            'title' => 'Enchère terminée',
            'body' => 'L’enchère « :auction » est terminée. Votre caution sera remboursée.',
        ],
        'payment_confirmed' => [
            'title' => 'Paiement confirmé',
            'body' => 'Votre paiement (:type) de :amount pour « :auction » est confirmé.',
        ],
        'payment_failed' => [
            'title' => 'Échec du paiement',
            'body' => 'Votre paiement (:type) pour « :auction » a échoué.',
        ],
        'final_payment_due' => [
            'title' => 'Paiement final dû',
            'body' => 'Effectuez le paiement final de « :auction » sous :days jours.',
        ],
        'deposit_refunded' => [
            'title' => 'Caution remboursée',
            'body' => 'Votre caution de :amount pour « :auction » a été remboursée.',
        ],
        'deposit_forfeited' => [
            'title' => 'Caution confisquée',
            'body' => 'Votre caution sur « :auction » a été confisquée faute de paiement.',
        ],
        'outbid' => [
            'title' => 'Vous avez été surenchéri',
            'body' => 'Une offre plus élevée (:amount) a été placée sur « :auction ».',
        ],
        'inspection_answered' => [
            'title' => 'Réponse à votre question',
            'body' => 'Votre question sur « :auction » a reçu une réponse.',
        ],
        'condition_book_published' => [
            'title' => 'Cahier des charges publié',
            'body' => 'Le cahier des charges de « :auction » est disponible.',
        ],
        'delivery_update' => [
            'title' => 'Mise à jour de la livraison',
            'body' => 'Statut de livraison de « :auction » : :status.',
        ],
        'appeal_updated' => [
            'title' => 'Mise à jour de votre recours',
            'body' => 'Statut de votre recours : :status.',
        ],
    ],
];
