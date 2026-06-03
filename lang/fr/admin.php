<?php

return [
    // ===== Layout: sidebar + topbar =====
    'panel' => 'Panneau d’administration',
    'nav_dashboard' => 'Tableau de bord',
    'nav_auctions' => 'Enchères',
    'nav_users' => 'Utilisateurs',
    'nav_kyc' => 'Vérification (KYC)',
    'nav_appeals' => 'Recours',
    'nav_audit' => 'Journal d’audit',
    'create_auction' => 'Créer une enchère',
    'logout' => 'Se déconnecter',
    'page_title_default' => 'Tableau de bord',

    // ===== Dashboard: stat tiles =====
    'stat_total_users' => 'Total des utilisateurs',
    'stat_pending_kyc' => 'Demandes de vérification en attente',
    'stat_active_auctions' => 'Enchères actives',
    'stat_total_bids' => 'Total des offres',

    // ===== Dashboard: tables =====
    'recent_auctions' => 'Dernières enchères',
    'recent_users' => 'Derniers utilisateurs',

    // Shared table headers
    'th_title' => 'Titre',
    'th_entity' => 'Entité',
    'th_category' => 'Catégorie',
    'th_price' => 'Prix',
    'th_bids' => 'Offres',
    'th_status' => 'Statut',
    'th_name' => 'Nom',
    'th_email' => 'E-mail',
    'th_role' => 'Rôle',
    'th_kyc' => 'Statut KYC',
    'th_registered' => 'Inscription',

    // ===== Auctions management (index + create/edit form) =====
    'auctions' => [
        'manage_title' => 'Gérer les enchères',
        'create_title' => 'Créer une nouvelle enchère',
        'edit_title' => 'Modifier l’enchère',
        'no_auctions' => 'Aucune enchère',
        'publish' => 'Publier',
        'start' => 'Démarrer',
        'confirm_delete' => 'Êtes-vous sûr de vouloir supprimer cette enchère ?',

        'sec_titles' => 'Titres et descriptions',
        'sec_classification' => 'Classification',
        'sec_pricing' => 'Tarification (en dinars)',
        'sec_scheduling' => 'Planification',
        'sec_entity' => 'Entité',
        'pricing_note' => 'Conversion automatique en centimes lors de l’envoi',

        'f_title_ar' => 'Titre (en arabe)',
        'f_title_fr' => 'Titre (en français)',
        'f_description_ar' => 'Description (en arabe)',
        'f_description_fr' => 'Description (en français)',
        'f_category' => 'Catégorie',
        'f_wilaya' => 'Wilaya',
        'f_auction_type' => 'Type d’enchère',
        'f_condition' => 'État du bien',
        'f_asset_location' => 'Emplacement du bien',
        'f_unit_count' => 'Nombre d’unités',
        'f_requires_cr' => 'Registre de commerce requis',
        'f_lease_duration' => 'Durée du bail (années)',
        'f_lease_renewals' => 'Nombre de renouvellements',
        'f_opening_price' => 'Prix de départ',
        'f_deposit' => 'Montant de la caution',
        'f_entry_fee' => 'Frais de participation',
        'f_book_price' => 'Prix du cahier des charges',
        'f_start_time' => 'Heure de début',
        'f_end_time' => 'Heure de fin',
        'f_entity' => 'Entité organisatrice',

        'choose_category' => '— Choisir la catégorie —',
        'choose_wilaya' => '— Choisir la wilaya —',
        'choose_type' => '— Choisir le type —',
        'choose_condition' => '— Choisir l’état —',
        'choose_entity' => '— Choisir l’entité —',

        'submit_create' => 'Créer l’enchère',
        'submit_edit' => 'Enregistrer les modifications',
    ],

    // ===== Users management =====
    'users' => [
        'manage_title' => 'Gérer les utilisateurs',
        'th_account_status' => 'Statut du compte',
        'blacklisted' => 'Liste noire',
        'blacklist_action' => 'Liste noire',
        'blacklist_reason_placeholder' => 'Motif du blocage...',
        'confirm_blacklist' => 'Confirmer le blocage',
        'confirm_blacklist_prompt' => 'Êtes-vous sûr de vouloir mettre cet utilisateur sur liste noire ?',
        'no_users' => 'Aucun utilisateur',
    ],

    // ===== KYC review =====
    'kyc' => [
        'manage_title' => 'Demandes de vérification d’identité',
        'th_email_short' => 'E-mail',
        'th_registration_date' => 'Date d’inscription',
        'approve' => 'Accepter',
        'reject' => 'Rejeter',
        'reject_reason_placeholder' => 'Motif du rejet...',
        'confirm_reject' => 'Confirmer le rejet',
        'no_pending' => 'Aucune demande de vérification en attente',
    ],

    // ===== Audit log =====
    'audit' => [
        'th_time' => 'Horodatage',
        'th_actor' => 'Acteur',
        'th_action' => 'Action',
        'th_resource' => 'Ressource',
        'no_logs' => 'Aucun journal',
    ],

    // ===== Controller flash / error messages =====
    'flash' => [
        'auction_created' => 'Enchère créée avec succès.',
        'auction_updated' => 'Enchère mise à jour avec succès.',
        'auction_deleted' => 'Enchère supprimée avec succès.',
        'auction_published' => 'Enchère publiée avec succès.',
        'auction_started' => 'Enchère démarrée avec succès.',
        'auction_edit_only_draft' => 'L’enchère ne peut être modifiée qu’à l’état de brouillon.',
        'auction_delete_has_bids' => 'Une enchère contenant des offres ne peut pas être supprimée.',
        'auction_publish_only_draft' => 'L’enchère doit être à l’état de brouillon pour être publiée.',
        'auction_start_only_published' => 'L’enchère doit être publiée pour être démarrée.',
        'kyc_approved' => 'Vérification acceptée avec succès.',
        'kyc_rejected' => 'Vérification rejetée.',
        'user_blacklisted' => 'L’utilisateur a été mis sur liste noire.',
    ],
];
