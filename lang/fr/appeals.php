<?php

return [
    // ===== Admin / entity: appeals management =====
    'manage_title' => 'Gérer les recours',
    'th_id' => 'N°',
    'th_user' => 'Utilisateur',
    'th_auction' => 'Enchère',
    'th_subject' => 'Objet',
    'reason_label' => 'Motif :',
    'response_label' => 'Réponse',
    'response_placeholder' => 'Écrivez votre réponse ici...',
    'none' => 'Aucun recours',

    // Workflow actions
    'forward_btn' => 'Transmettre à l’organisme',
    'reject_intake_label' => 'Motif du rejet',
    'reject_intake_btn' => 'Rejet direct',
    'awaiting_entity' => 'En attente de la décision de l’organisme.',
    'entity_decision_label' => 'Décision de l’organisme :',
    'entity_response_label' => 'Réponse de l’organisme :',
    'entity_response_field' => 'Réponse de l’organisme',
    'entity_response_placeholder' => 'Justifiez la décision...',
    'entity_no_action' => 'Aucune action requise sur ce recours pour le moment.',
    'final_decision_label' => 'Décision finale',
    'decision_approve' => 'Accepter',
    'decision_reject' => 'Rejeter',
    'confirm_btn' => 'Confirmer la décision',

    // ===== Citizen: appeal on the auction page =====
    'tab' => 'Recours',
    'auction_tab_title' => 'Déposer un recours contre le résultat',
    'your_appeal_title' => 'Votre recours sur cette enchère',
    'window_hint' => 'Vous pouvez déposer un seul recours dans les :days jours suivant la clôture de l’enchère.',
    'subject' => 'Objet',
    'subject_placeholder' => 'Titre du recours',
    'details' => 'Détails',
    'details_placeholder' => 'Expliquez en détail le motif du recours...',
    'submit' => 'Envoyer le recours',
    'submitted_on' => 'Déposé le',

    // ===== Citizen: my appeals (tracking) =====
    'submitted_list' => 'Recours soumis',
    'admin_response' => 'Réponse de l’administration :',
    'auction_ref' => 'Enchère :',
    'none_submitted' => 'Aucun recours soumis',
    'file_from_auction_hint' => 'Les recours se déposent depuis la page de l’enchère, après sa clôture.',

    // ===== Flash + error messages =====
    'flash_submitted' => 'Votre recours a été soumis avec succès.',
    'flash_forwarded' => 'Le recours a été transmis à l’organisme.',
    'flash_entity_decided' => 'La décision de l’organisme a été enregistrée.',
    'flash_responded' => 'Le recours a été tranché avec succès.',
    'error_not_eligible' => 'Vous ne pouvez pas déposer de recours sur cette enchère.',
    'error_already_filed' => 'Vous avez déjà déposé un recours sur cette enchère.',
    'error_invalid_transition' => 'Cette action est impossible dans l’état actuel du recours.',
    'error_invalid_decision' => 'Décision invalide.',
];
