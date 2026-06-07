<?php

return [
    'common' => [
        'doc_id' => 'N° du document',
        'issued_at' => 'Date d’émission',
        'verify_footer' => 'Ce document est généré et signé électroniquement. Vérifiez son authenticité sur :',
        'electronic_signature' => 'Signature électronique — conforme à la loi 15-04',
    ],

    'condition_book' => [
        'title' => 'Cahier des charges — :auction',
        'asset_section' => 'Informations sur le bien',
        'opening_price' => 'Prix de départ',
        'deposit' => 'Montant de la caution',
        'book_price' => 'Prix du cahier',
        'start' => 'Début de l’enchère',
        'description' => 'Description',
        'terms' => 'Conditions',
        'terms_body' => 'Le participant s’engage à respecter le cahier des charges et les lois algériennes applicables. La lecture du cahier est requise pour s’inscrire.',
        'requires_commerce_register' => 'Ce bien exige un registre du commerce valide.',
        'requires_newspaper' => 'Cette enchère est annoncée dans un journal national en plus de la plateforme.',
    ],

    'award' => [
        'title' => 'Procès-verbal d’adjudication — :auction',
        'winner_section' => 'Informations sur l’adjudicataire',
        'winner_name' => 'Nom',
        'winner_nin' => 'N° d’identification national',
        'winner_phone' => 'Téléphone',
        'winner_address' => 'Adresse',
        'asset_section' => 'Informations sur le bien',
        'asset_title' => 'Intitulé',
        'category' => 'Catégorie',
        'condition' => 'État',
        'unit_count' => 'Nombre d’unités',
        'location' => 'Emplacement',
        'auction_section' => 'Informations sur l’enchère',
        'auction_id' => 'N° de l’enchère',
        'entity' => 'Entité',
        'closed_at' => 'Date de clôture',
        'financial_section' => 'Récapitulatif financier',
        'delivery_section' => 'Conditions de livraison',
        'delivery_note' => 'La livraison a lieu après le paiement intégral sous :days jours, sur présentation du procès-verbal et du reçu de paiement.',
        'legal_notice' => 'Document juridiquement contraignant au titre du Code civil (art. 96) et du Code de procédure civile. La propriété est transférée après paiement intégral.',
    ],

    'receipt' => [
        'title' => 'Reçu de paiement — :type',
        'section' => 'Détails du paiement',
        'payer' => 'Payeur',
        'payment_type' => 'Type de paiement',
        'amount' => 'Montant',
        'status' => 'Statut',
        'reference' => 'Référence',
        'confirmed_at' => 'Date de confirmation',
    ],

    'delivery_report' => [
        'title' => 'Procès-verbal de livraison — :auction',
        'section' => 'Informations de livraison',
        'recipient' => 'Bénéficiaire',
        'scheduled_at' => 'Rendez-vous prévu',
        'delivered_at' => 'Date de livraison',
        'address' => 'Adresse',
        'status' => 'Statut',
    ],

    'verify' => [
        'title' => 'Vérification du document',
        'valid_title' => 'Document valide',
        'valid_intro' => 'Ce document a été émis par Mazayada et signé électroniquement.',
        'invalid_title' => 'Document invalide',
        'invalid_intro' => 'Impossible de vérifier ce document. Le lien est peut-être incorrect ou le document a été modifié.',
        'doc_type' => 'Type de document',
        'issued_at' => 'Date d’émission',
        'auction' => 'Enchère',
    ],
];
