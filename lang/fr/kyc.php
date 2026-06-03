<?php

return [
    // Middleware / guard messages (App\Http\Middleware\KycVerified).
    'complete_required' => 'Vous devez compléter la vérification d’identité avant de continuer.',
    'not_authorized' => 'Vous n’êtes pas autorisé à effectuer cette action.',

    // ===== Citizen KYC page =====
    'page_title' => 'Vérification d’identité (KYC)',
    'page_subtitle' => 'Complétez votre vérification d’identité pour participer aux enchères',

    'step1_label' => 'Étape 1',
    'step1_title' => 'Documents biométriques',
    'step2_label' => 'Étape 2',
    'step2_title' => 'Informations personnelles',
    'step3_label' => 'Étape 3',
    'step3_title' => 'Envoi et examen',

    'doc_id_front' => 'Recto de la carte d’identité',
    'doc_id_back' => 'Verso de la carte d’identité',
    'doc_selfie' => 'Selfie avec la carte d’identité',
    'uploaded' => 'Téléversé ✓',
    'upload_hint' => 'JPG ou PNG — 5 Mo maximum',

    'requirements_title' => 'Exigences photo :',
    'req_clear' => 'Photo nette sans reflets',
    'req_readable' => 'Tous les coins et textes lisibles',
    'req_size' => 'Taille maximale : 5 Mo par fichier',
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
    'f_expected_income' => 'Revenu annuel prévu (DA)',
    'f_rip' => 'Numéro de compte bancaire (RIP)',

    'choose_wilaya' => '— Choisir la wilaya —',
    'choose_wilaya_first' => '— Choisir d’abord la wilaya —',
    'choose_commune' => '— Choisir la commune —',
    'js_load_error' => 'Erreur de chargement',

    'submit' => 'Envoyer la demande de vérification',

    // ===== Controller flash messages =====
    'file_type_not_allowed' => 'Type de fichier non autorisé.',
    'file_uploaded' => 'Fichier téléversé avec succès.',
    'info_saved' => 'Informations personnelles enregistrées avec succès.',
];
