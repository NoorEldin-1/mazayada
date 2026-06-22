<?php

return [
    'kyc_status' => [
        'PENDING' => 'En attente',
        'UNDER_REVIEW' => 'En cours d’examen',
        'COMPLETE' => 'Vérifié',
        'REJECTED' => 'Rejeté',
        'SUSPENDED' => 'Suspendu',
    ],

    'auction_status' => [
        'DRAFT' => 'Brouillon',
        'PUBLISHED' => 'Publiée',
        'ACTIVE' => 'Active',
        'EXTENDED' => 'Prolongée',
        'CLOSED' => 'Clôturée',
        'CANCELLED' => 'Annulée',
    ],

    'user_role' => [
        'CITIZEN' => 'Citoyen',
        'PREMIUM_CITIZEN' => 'Citoyen Premium',
        'SUPER_ADMIN' => 'Super administrateur',
        'ENTITY_HEAD' => 'Chef d’entité',
        'APPRAISER' => 'Évaluateur',
        'HUISSIER' => 'Huissier de justice',
        'COMMITTEE_MEMBER' => 'Membre du comité',
        'CONTENT_ADMIN' => 'Administrateur de contenu',
        'ENTITY_VIEWER' => 'Compte entité (lecture seule)',
    ],

    'auction_type' => [
        'SALE' => 'Vente',
        'LEASE' => 'Location',
    ],

    'asset_condition' => [
        'NEW' => 'Neuf',
        'GOOD' => 'Bon',
        'FAIR' => 'Correct',
        'POOR' => 'Mauvais',
        'SCRAP' => 'Ferraille',
    ],

    'account_status' => [
        'ACTIVE' => 'Actif',
        'SUSPENDED' => 'Suspendu',
        'BANNED' => 'Banni',
    ],

    'account_type' => [
        'PERSON' => 'Personne',
        'INSTITUTION' => 'Entité',
    ],

    'appeal_status' => [
        'SUBMITTED' => 'Soumis',
        'UNDER_REVIEW' => 'En cours d’examen',
        'RESOLVED' => 'Résolu',
        'REJECTED' => 'Rejeté',
        'ESCALATED' => 'Escaladé',
    ],

    'entity_type' => [
        'CUSTOMS' => 'Direction Générale des Douanes',
        'STATE_PROPERTIES' => 'Domaine de l’État',
        'MUNICIPALITY' => 'Assemblées populaires communales',
        'JUDICIAL' => 'Huissiers de justice',
        'TAX' => 'Direction Générale des Impôts',
    ],

    'payment_type' => [
        'DEPOSIT' => 'Caution',
        'ENTRY_FEE' => 'Frais de participation',
        'BOOK_PURCHASE' => 'Cahier des charges',
        'FINAL_PAYMENT' => 'Paiement final',
    ],

    'payment_status' => [
        'PENDING' => 'En attente',
        'CONFIRMED' => 'Confirmé',
        'REFUNDED' => 'Remboursé',
        'FORFEITED' => 'Confisqué',
        'FAILED' => 'Échoué',
    ],

    'id_document_type' => [
        'ID_CARD' => 'Carte nationale d\'identité',
        'PASSPORT' => 'Passeport',
        'LICENSE' => 'Permis de conduire',
    ],

    'asset_class' => [
        'MOVABLE' => 'Bien meuble',
        'REAL_ESTATE' => 'Bien immobilier',
        'CUSTOMS' => 'Marchandises douanières',
    ],

    'delivery_status' => [
        'SCHEDULED' => 'Planifiée',
        'IN_TRANSIT' => 'En cours',
        'DELIVERED' => 'Livrée',
        'FAILED' => 'Échec',
        'CANCELLED' => 'Annulée',
    ],

    'inspection_question_status' => [
        'PENDING' => 'En attente',
        'ANSWERED' => 'Répondue',
        'REJECTED' => 'Rejetée',
    ],

    'document_type' => [
        'CONDITION_BOOK' => 'Cahier des charges',
        'AWARD' => 'Procès-verbal d’adjudication',
        'PAYMENT_RECEIPT' => 'Reçu de paiement',
        'DELIVERY_REPORT' => 'Procès-verbal de livraison',
    ],

    'notification_channel' => [
        'PUSH' => 'Notification push',
        'SMS' => 'SMS',
        'EMAIL' => 'E-mail',
        'IN_APP' => 'Dans l’application',
    ],
];
