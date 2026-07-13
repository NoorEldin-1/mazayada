<?php

return [
    // ===== Page =====
    'page_title' => 'Registre de commerce',
    'page_subtitle' => 'Soumettez les données et documents de votre registre de commerce pour participer aux enchères qui l’exigent',

    // ===== Sections =====
    'sec_data_title' => 'Données du registre de commerce',
    'sec_docs_title' => 'Documents joints',

    // ===== Fields =====
    'f_company_name' => 'Nom de l’entreprise / entité commerciale',
    'f_register_number' => 'Numéro du registre de commerce',
    'f_tax_number' => 'Numéro fiscal (carte fiscale)',
    'f_activity_type' => 'Type d’activité commerciale',
    'f_start_date' => 'Date de début du registre de commerce',
    'f_register_document' => 'Copie du registre de commerce',
    'f_tax_card_document' => 'Copie de la carte fiscale',

    'view_current_file' => 'Voir le fichier actuel',
    'open_pdf' => 'Ouvrir le fichier (PDF)',
    'upload_hint' => 'PDF ou image JPG/PNG — max. 2 Mo',
    'submit' => 'Envoyer la demande',

    // ===== Status banners =====
    'banner_none_title' => 'Vous n’avez pas encore soumis votre registre de commerce',
    'banner_none_text' => 'Remplissez les données et téléversez les documents ci-dessous pour envoyer votre demande.',
    'banner_pending_title' => 'Votre demande est en cours d’examen',
    'banner_pending_text' => 'Votre demande a été envoyée le :date et est en cours d’examen par l’administration.',
    'banner_approved_title' => 'Votre registre de commerce est approuvé',
    'banner_approved_text' => 'Votre registre de commerce est approuvé et vous pouvez participer aux enchères qui l’exigent.',
    'banner_rejected_title' => 'Registre de commerce rejeté',
    'banner_rejected_reason' => 'Motif du rejet :',
    'banner_rejected_hint' => 'Veuillez corriger les données ou téléverser à nouveau les documents, puis renvoyer la demande.',

    // ===== Validation / flash =====
    'start_must_not_be_future' => 'La date de début du registre de commerce ne peut pas être dans le futur.',
    'submitted_success' => 'Votre demande de registre de commerce a été envoyée avec succès et est en cours d’examen.',

    // ===== Notification copy (in-app, stored in the user's language) =====
    'notif_approved_title' => 'Votre registre de commerce est approuvé',
    'notif_approved_body' => 'Votre registre de commerce a été approuvé. Vous pouvez désormais participer aux enchères qui l’exigent.',
    'notif_rejected_title' => 'Registre de commerce rejeté',
    'notif_rejected_body' => 'Votre registre de commerce a été rejeté. Motif : :reason. Veuillez corriger et renvoyer.',

    // ===== Verified-merchant badge (dashboard dropdown + sidebar) =====
    'badge_verified' => 'Marchand vérifié',
    'badge_verified_hint' => 'Registre de commerce vérifié et en cours de validité',
];
