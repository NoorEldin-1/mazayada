<?php

return [
    // Module + navigation
    'manage_title' => 'Rapports des ventes',
    'none' => 'Aucun rapport pour le moment.',

    // Row-action submenu (auctions table)
    'menu_report' => 'Rapport de la vente',
    'action_generate' => 'Émettre un rapport',
    'action_view' => 'Voir le rapport',
    'action_refer' => 'Transmettre à l’organisme',

    // Module table
    'th_seq' => 'N°',
    'th_auction' => 'Vente',
    'th_status' => 'Statut',
    'th_generated_by' => 'Émis par',
    'th_referral' => 'Transmission',
    'referred_badge' => 'Transmis',
    'not_referred_badge' => 'Non transmis',
    'confirm_refer' => 'Transmettre ce rapport à l’organisme organisateur ? Il apparaîtra dans sa rubrique des rapports de ventes.',

    // Flash messages
    'flash_referred' => 'Le rapport a été transmis à l’organisme organisateur.',
    'flash_no_report' => 'Aucun rapport n’a encore été émis pour cette vente.',
    'flash_missing_file' => 'Le fichier du rapport est introuvable.',
    'flash_generate_failed' => 'Échec de l’émission du rapport, veuillez réessayer.',

    // ===== PDF document =====
    'doc_title' => 'Rapport de la vente n° :seq — :auction',
    'legal_notice' => 'Ce rapport est un document administratif signé électroniquement reflétant les données de la vente au moment de son émission. Son authenticité est vérifiable via le code QR.',
    'fees_note' => 'Détail des frais judiciaires et de la TVA (décret 97-33) calculés sur le prix de référence ci-dessous.',

    // Section 1 — identity
    'sec_identity' => 'Identité de la vente',
    'f_title_ar' => 'Intitulé (arabe)',
    'f_title_fr' => 'Intitulé (français)',
    'f_title_en' => 'Intitulé (anglais)',
    'f_id' => 'Identifiant de la vente',
    'f_entity' => 'Organisme organisateur',
    'f_category' => 'Catégorie',
    'f_type' => 'Type de vente',
    'f_asset_class' => 'Classe du bien',
    'f_condition' => 'État physique',
    'f_unit_count' => 'Nombre d’unités',
    'f_created_by' => 'Créé par',

    // Section 2 — lifecycle
    'sec_lifecycle' => 'Chronologie',
    'f_status' => 'Statut',
    'f_start' => 'Heure de début',
    'f_end' => 'Heure de fin',
    'f_extensions' => 'Nombre de prolongations',
    'f_closed_at' => 'Heure de clôture',
    'f_settled_at' => 'Heure de règlement',
    'f_inspection' => 'Fenêtre d’inspection',

    // Section 3 — financials
    'sec_financials' => 'Données financières',
    'f_opening_price' => 'Prix d’ouverture',
    'f_deposit' => 'Montant de la caution',
    'f_entry_fee' => 'Droit de participation',
    'f_book_price' => 'Prix du cahier des charges',
    'f_current_price' => 'Prix actuel',
    'f_final_price' => 'Prix final',

    // Section 4 — bidding
    'sec_bidding' => 'Résumé des enchères',
    'f_bid_count' => 'Nombre d’offres valides',
    'f_participants' => 'Nombre de participants',
    'th_bidder' => 'Enchérisseur',
    'th_amount' => 'Montant',
    'th_time' => 'Heure',
    'no_bids' => 'Aucune offre.',

    // Section 5 — winner
    'sec_winner' => 'Adjudicataire',
    'f_winner_name' => 'Nom',
    'f_winner_nin' => 'Numéro d’identification national',
    'f_winner_phone' => 'Téléphone',

    // Section 6 — payments
    'sec_payments' => 'Journal des paiements',
    'th_pay_type' => 'Type de paiement',
    'th_payer' => 'Payeur',
    'th_status' => 'Statut',
    'th_date' => 'Date',
    'no_payments' => 'Aucun paiement.',

    // Section 7 — documents
    'sec_documents' => 'Documents émis',
    'th_doc_type' => 'Type',
    'th_doc_title' => 'Intitulé',
    'no_documents' => 'Aucun document.',

    // Section 8 — appeals
    'sec_appeals' => 'Recours',
    'th_subject' => 'Objet',
    'no_appeals' => 'Aucun recours sur cette vente.',

    // Section 9 — delivery
    'sec_delivery' => 'Livraison',
    'f_delivery_status' => 'Statut de livraison',
    'f_delivery_date' => 'Date de livraison',
    'no_delivery' => 'Aucune livraison enregistrée.',

    // Section 10 — location
    'sec_location' => 'Emplacement du bien',
    'f_location' => 'Adresse',
    'f_wilaya' => 'Wilaya',
    'f_commune' => 'Commune',
    'f_coords' => 'Coordonnées',

    // Section 11 — specifications
    'sec_specs' => 'Spécifications',

    // Section 12 — issue metadata
    'sec_issue' => 'Données d’émission',
    'f_sequence' => 'N° du rapport',
    'f_issued_at' => 'Date d’émission',
];
