<?php

return [
    // Middleware / guard messages (App\Http\Middleware\KycVerified).
    'complete_required' => 'Vous devez compléter la vérification d’identité avant de continuer.',
    'not_authorized' => 'Vous n’êtes pas autorisé à effectuer cette action.',
    'flash_under_review' => 'Votre demande de vérification d’identité est en cours d’examen.',
    'flash_rejected' => 'Votre demande a été rejetée — veuillez corriger vos informations et renvoyer.',
    'flash_suspended' => 'Votre compte a été suspendu pour vérification d’identité non complétée à temps.',
    'locked' => 'Modification impossible pendant l’examen ou après la validation de la demande.',

    // ===== Citizen KYC page =====
    'page_title' => 'Vérification d’identité (KYC)',
    'page_subtitle' => 'Complétez votre vérification d’identité pour participer aux enchères',

    'step1_label' => 'Étape 1',
    'step1_title' => 'Documents biométriques',
    'step2_label' => 'Étape 2',
    'step2_title' => 'Informations personnelles',
    'step3_label' => 'Étape 3',
    'step3_title' => 'Envoi et examen',
    'step3_hint' => 'Vérifiez vos informations et documents, puis envoyez la demande pour examen par l’administration.',

    // ===== Status banners =====
    'banner_pending_text' => 'Vous n’avez pas encore complété votre vérification d’identité — complétez-la pour participer aux enchères.',
    'banner_cta' => 'Compléter la vérification',
    'banner_under_review_title' => 'Votre demande est en cours d’examen',
    'banner_under_review_text' => 'Votre demande a été envoyée le :date et est en cours d’examen par l’administration. Vous serez notifié du résultat prochainement.',
    'banner_complete_title' => 'Votre identité est vérifiée',
    'banner_complete_text' => 'Votre identité est vérifiée et vous pouvez participer aux enchères.',
    'banner_rejected_title' => 'Demande de vérification rejetée',
    'banner_rejected_reason' => 'Motif du rejet :',
    'banner_rejected_hint' => 'Veuillez corriger vos informations ou re-téléverser vos documents, puis renvoyer la demande.',
    'banner_suspended_title' => 'Compte suspendu',
    'banner_suspended_text' => 'Votre compte a été suspendu pour vérification non complétée à temps. Complétez-la pour le réactiver.',

    'doc_id_front' => 'Recto de la carte d’identité',
    'doc_id_back' => 'Verso de la carte d’identité',
    'doc_selfie' => 'Selfie avec la carte d’identité',
    'uploaded' => 'Téléversé ✓',
    'uploaded_replace' => 'Téléversé ✓ — cliquez pour remplacer',
    'upload_hint' => 'JPG ou PNG — 2 Mo maximum',

    'requirements_title' => 'Exigences photo :',
    'req_clear' => 'Photo nette sans reflets',
    'req_readable' => 'Tous les coins et textes lisibles',
    'req_size' => 'Taille maximale : 2 Mo par fichier',
    'req_formats' => 'Formats acceptés : JPG, PNG',

    'f_first_name_fr' => 'Prénom (en français)',
    'f_last_name_fr' => 'Nom (en français)',
    'f_father_name' => 'Nom du père',
    'f_mother_fullname' => 'Nom complet de la mère',
    'f_wilaya' => 'Wilaya',
    'f_commune' => 'Commune',
    'f_full_address' => 'Adresse complète',
    'f_postal_code' => 'Code postal',
    'f_profession' => 'Profession',
    'f_expected_income' => 'Revenu mensuel prévu (DA)',
    'f_rip' => 'Numéro de compte bancaire (RIP)',

    'choose_wilaya' => '— Choisir la wilaya —',
    'choose_wilaya_first' => '— Choisir d’abord la wilaya —',
    'choose_commune' => '— Choisir la commune —',
    'js_load_error' => 'Erreur de chargement',

    'submit' => 'Envoyer la demande de vérification',

    // ===== Controller flash / validation messages =====
    'file_type_not_allowed' => 'Type de fichier non autorisé.',
    'file_uploaded' => 'Fichier téléversé avec succès.',
    'info_saved' => 'Informations personnelles enregistrées avec succès.',
    'submitted_success' => 'Votre demande de vérification a été envoyée et est en cours d’examen.',
    'error_docs_required' => 'Vous devez téléverser les trois documents (recto et verso de la carte + selfie) avant l’envoi.',
    'commune_wilaya_mismatch' => 'La commune choisie n’appartient pas à la wilaya sélectionnée.',
    'postal_code_invalid' => 'Le code postal doit comporter 5 chiffres.',

    // ===== Notification copy (in-app, stored in the user's language) =====
    'notif_approved_title' => 'Votre identité est vérifiée',
    'notif_approved_body' => 'Votre demande de vérification a été approuvée. Vous pouvez désormais participer aux enchères.',
    'notif_rejected_title' => 'Demande de vérification rejetée',
    'notif_rejected_body' => 'Votre demande de vérification a été rejetée. Motif : :reason. Veuillez corriger et renvoyer.',
    'notif_suspended_title' => 'Compte suspendu',
    'notif_suspended_body' => 'Votre compte a été suspendu pour vérification d’identité non complétée à temps.',

    'doc_photo_biometric' => 'Photo biométrique (optionnel)',
    'doc_photo_biometric_hint' => '35×45mm, fond blanc, max 120 Ko',
    'f_id_type' => 'Type de pièce d\'identité',
    'id_type_none' => '— Choisir le type —',
    'f_id_number' => 'Numéro de la pièce d\'identité',
    'f_nif' => 'Numéro fiscal (NIF)',
    'f_nis' => 'Numéro statistique (NIS)',
];
