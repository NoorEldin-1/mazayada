<?php

return [
    // ===== Auction card / listing labels (reused on landing + listings) =====
    'live' => 'En direct',
    'coming_soon' => 'Bientôt',
    'current_price' => 'Prix actuel',
    'starting_price' => 'Prix de départ',
    'bids_word' => 'offres',
    'general_category' => 'Général',
    'upcoming_title' => 'Enchère à venir',
    'default_location' => 'Algérie',

    // ===== Bidding service errors (thrown from App\Services\BiddingService) =====
    'bid' => [
        'invalid_amount' => 'Montant invalide.',
        'not_eligible' => 'Vous ne remplissez pas les conditions de l’enchère (KYC ou statut).',
        'rate_limited' => 'Vous avez dépassé la limite d’enchères (:max par minute).',
        'not_active' => 'L’enchère n’est pas active actuellement.',
        'ended' => 'L’enchère est terminée.',
        'must_register' => 'Vous devez d’abord vous inscrire et payer la caution.',
        'too_low' => 'Le montant doit être supérieur au prix actuel.',
        'failed' => 'Échec de l’enregistrement de l’enchère, veuillez réessayer.',
    ],

    // ===== Public listing (auctions/index) =====
    'browse' => [
        'total_prefix' => 'Total',
        'total_suffix' => 'enchères disponibles',
        'filter_category' => 'Catégorie',
        'filter_wilaya' => 'Wilaya',
        'filter_status' => 'Statut',
        'filter_type' => 'Type',
        'none_title' => 'Aucune enchère',
        'none_desc' => 'Aucune enchère ne correspond à vos critères de recherche.',
    ],

    // ===== Auction detail (auctions/show) =====
    'show' => [
        'back' => 'Retour aux enchères',
        'tab_details' => 'Détails',
        'tab_specs' => 'Caractéristiques',
        'tab_bids' => 'Historique des offres',
        'desc_title' => 'Description de l’enchère',
        'no_desc' => 'Aucune description disponible pour cette enchère.',
        'spec_opening' => 'Prix de départ',
        'spec_deposit' => 'Caution',
        'spec_entry' => 'Frais de participation',
        'spec_book' => 'Prix du cahier',
        'spec_units' => 'Nombre d’unités',
        'spec_wilaya' => 'Wilaya',
        'spec_condition' => 'État',
        'spec_type' => 'Type',
        'recent_prefix' => 'Dernières',
        'th_bidder' => 'Enchérisseur',
        'th_amount' => 'Montant',
        'th_time' => 'Heure',
        'no_bids' => 'Aucune offre pour le moment',
        'bids_so_far' => 'offres jusqu’à présent',
        'cd_hours' => 'heures',
        'cd_minutes' => 'minutes',
        'cd_seconds' => 'secondes',
        'login_to_participate' => 'Connectez-vous pour participer',
        'register_in' => 'S’inscrire à cette enchère',
        'amount_placeholder' => 'Montant en centimes',
        'place_bid' => 'Placer votre offre',
        'closed' => 'Enchère clôturée',
        'winner_label' => 'Gagnant :',
        'no_winner' => 'Aucun gagnant désigné',
        'not_started' => 'L’enchère n’a pas encore commencé',
        'recent_bids' => 'Offres récentes',
        'no_bids_side' => 'Aucune offre pour le moment',
    ],

    // ===== Controller flash messages =====
    'flash_registered' => 'Inscription à l’enchère réussie.',
    'flash_already_registered' => 'Vous êtes déjà inscrit à cette enchère.',
    'flash_bid_placed' => 'Votre offre a été placée avec succès.',
    'bid_too_low_priced' => 'Le montant doit être supérieur au prix actuel (:price).',
];
