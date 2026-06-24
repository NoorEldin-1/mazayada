<?php

return [
    // ===== OTP verification email (registration) =====
    'otp' => [
        'subject' => 'Code de vérification de votre e-mail — Mazayada',
        'greeting' => 'Bonjour :name,',
        'intro' => 'Utilisez le code suivant pour confirmer votre adresse e-mail et finaliser la création de votre compte Mazayada :',
        'code_label' => 'Code de vérification',
        'expiry' => 'Ce code n’est valable que pendant :minutes minutes.',
        'ignore' => 'Si vous n’avez pas demandé ce code, vous pouvez ignorer ce message en toute sécurité.',
        'footer' => 'Ceci est un message automatique de Mazayada, merci de ne pas y répondre.',
    ],

    // ===== OTP email (password reset) =====
    'reset' => [
        'subject' => 'Votre code de réinitialisation de mot de passe — Mazayada',
        'greeting' => 'Bonjour :name,',
        'intro' => 'Utilisez le code suivant pour réinitialiser le mot de passe de votre compte Mazayada :',
        'code_label' => 'Code de vérification',
        'expiry' => 'Ce code n’est valable que pendant :minutes minutes.',
        'ignore' => 'Si vous n’avez pas demandé de réinitialisation de mot de passe, vous pouvez ignorer ce message en toute sécurité.',
        'footer' => 'Ceci est un message automatique de Mazayada, merci de ne pas y répondre.',
    ],

    // ===== KYC decision emails =====
    'kyc_approved' => [
        'subject' => 'Votre identité est vérifiée — Mazayada',
        'greeting' => 'Bonjour :name,',
        'intro' => 'Nous avons le plaisir de vous informer que votre demande de vérification d’identité a été approuvée. Vous pouvez désormais participer aux enchères sur Mazayada.',
        'cta' => 'Accéder à mon compte',
        'footer' => 'Ceci est un message automatique de Mazayada, merci de ne pas y répondre.',
    ],
    'kyc_rejected' => [
        'subject' => 'Demande de vérification d’identité rejetée — Mazayada',
        'greeting' => 'Bonjour :name,',
        'intro' => 'Nous regrettons de vous informer que votre demande de vérification d’identité a été rejetée. Vous pouvez corriger vos informations et renvoyer la demande.',
        'reason_label' => 'Motif du rejet',
        'cta' => 'Corriger la demande',
        'footer' => 'Ceci est un message automatique de Mazayada, merci de ne pas y répondre.',
    ],
    'kyc_suspended' => [
        'subject' => 'Votre compte a été suspendu — Mazayada',
        'greeting' => 'Bonjour :name,',
        'intro' => 'Votre compte a été suspendu car la vérification d’identité n’a pas été complétée dans le délai imparti. Complétez-la pour réactiver votre compte.',
        'cta' => 'Compléter la vérification',
        'footer' => 'Ceci est un message automatique de Mazayada, merci de ne pas y répondre.',
    ],

    // ===== Auction lifecycle event emails (spec §10.1) =====
    'events_common' => [
        'greeting' => 'Bonjour :name,',
        'cta' => 'Voir les détails',
        'footer' => 'Ceci est un message automatique de Mazayada, merci de ne pas y répondre.',
    ],
    'events' => [
        'auction_won' => [
            'subject' => 'Félicitations ! Vous avez remporté l’enchère — Mazayada',
            'line' => 'Vous avez remporté l’enchère « :auction » pour :amount. Le paiement final doit être effectué sous :days jours.',
        ],
        'auction_lost' => [
            'subject' => 'Enchère terminée — Mazayada',
            'line' => 'L’enchère « :auction » est terminée et vous n’étiez pas le plus offrant. Votre caution sera remboursée automatiquement.',
        ],
        'payment_confirmed' => [
            'subject' => 'Paiement confirmé — Mazayada',
            'line' => 'Votre paiement (:type) de :amount pour l’enchère « :auction » a été confirmé.',
        ],
        'payment_failed' => [
            'subject' => 'Échec du paiement — Mazayada',
            'line' => 'Votre paiement (:type) pour l’enchère « :auction » n’a pas abouti. Vous pouvez réessayer.',
        ],
        'final_payment_due' => [
            'subject' => 'Paiement final dû — Mazayada',
            'line' => 'Le paiement final de l’enchère « :auction » doit être effectué sous :days jours, sinon la caution sera confisquée.',
        ],
        'deposit_refunded' => [
            'subject' => 'Caution remboursée — Mazayada',
            'line' => 'Votre caution de :amount pour l’enchère « :auction » a été remboursée.',
        ],
        'deposit_forfeited' => [
            'subject' => 'Caution confisquée — Mazayada',
            'line' => 'Faute de paiement final dans les délais pour l’enchère « :auction », votre caution a été confisquée et votre compte mis sur liste noire.',
        ],
        'outbid' => [
            'subject' => 'Vous avez été surenchéri — Mazayada',
            'line' => 'Une offre plus élevée de :amount a été placée sur l’enchère « :auction ». Placez une nouvelle offre.',
        ],
        'inspection_answered' => [
            'subject' => 'Réponse à votre question — Mazayada',
            'line' => 'Une réponse a été apportée à votre question concernant l’enchère « :auction ».',
        ],
        'condition_book_published' => [
            'subject' => 'Cahier des charges publié — Mazayada',
            'line' => 'Le cahier des charges de l’enchère « :auction » est disponible. Consultez-le avant de vous inscrire.',
        ],
        'delivery_update' => [
            'subject' => 'Mise à jour de la livraison — Mazayada',
            'line' => 'Le statut de livraison de l’enchère « :auction » est passé à : :status.',
        ],
        'appeal_updated' => [
            'subject' => 'Mise à jour de votre recours — Mazayada',
            'line' => 'Le statut de votre recours est passé à : :status.',
        ],
        'appeal_submitted' => [
            'subject' => 'Nouveau recours — Mazayada',
            'line' => 'Un nouveau recours a été déposé sur l’enchère « :auction » et attend examen.',
        ],
        'appeal_forwarded' => [
            'subject' => 'Recours qui vous est transmis — Mazayada',
            'line' => 'Un recours sur l’enchère « :auction » vous a été transmis pour décision.',
        ],
        'appeal_entity_decided' => [
            'subject' => 'Décision de l’organisme sur un recours — Mazayada',
            'line' => 'L’organisme a rendu sa décision (:decision) sur un recours relatif à l’enchère « :auction », en attente de votre confirmation.',
        ],
    ],
];
