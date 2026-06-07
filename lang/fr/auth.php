<?php

return [
    // ===== Auth layout — marketing side panel =====
    'left_badge' => 'Plateforme Nationale des Enchères',
    'left_title' => 'La première plateforme numérique d’enchères en Algérie',
    'left_desc' => 'Rejoignez des milliers d’utilisateurs qui participent aux enchères publiques en toute transparence et sécurité via notre plateforme numérique.',
    'testimonial' => 'Mazayada a transformé notre façon de participer aux enchères publiques. Un processus transparent et facile à utiliser.',
    'testimonial_name' => 'Mohamed Ben Omar',
    'testimonial_role' => 'Directeur d’une entreprise de BTP',
    'stat_active_auctions' => 'enchères actives',
    'stat_registered_users' => 'utilisateurs inscrits',
    'stat_wilayas' => 'wilayas',

    // ===== Marketing side panel — instructions carousel =====
    'carousel' => [
        'aria_label' => 'Diapositives d’instructions et conditions de la plateforme',
        'prev' => 'Diapositive précédente',
        'next' => 'Diapositive suivante',
        'go_to' => 'Aller à la diapositive :num',

        // Slide 1 — entry rules
        'input_title' => 'Règles de saisie',
        'input_nin' => 'Numéro d’identification nationale (NIN) : exactement 18 chiffres.',
        'input_phone' => 'Téléphone : 10 chiffres commençant par 05, 06 ou 07.',
        'input_age' => 'Participation réservée aux personnes âgées de 18 ans et plus.',

        // Slide 2 — KYC
        'kyc_title' => 'Étapes de vérification (KYC)',
        'kyc_body' => 'Après l’inscription, une vérification d’identité est obligatoire : téléversez vos documents et un selfie. Effectuez-la à temps pour éviter la suspension de votre compte.',

        // Slide 3 — security & data protection
        'security_title' => 'Sécurité et protection des données',
        'security_body' => 'Plateforme gouvernementale sécurisée, conforme à la loi algérienne sur la protection des données (18-07). Vos données restent en Algérie et ne sont jamais transférées à l’étranger.',

        // Slide 4 — how to join auctions
        'auction_title' => 'Comment participer aux enchères',
        'auction_body' => 'Pour participer à une enchère, réglez des frais d’inscription et une caution (remboursable) par carte bancaire, puis enchérissez directement.',
    ],

    // ===== Shared field labels & placeholders =====
    'nin_label' => 'Numéro d’identification nationale (NIN)',
    'login_id_label' => 'Numéro d’identification nationale ou e-mail',
    'login_id_placeholder' => 'Identifiant national ou e-mail',
    'first_name_ar_label' => 'Prénom (en arabe)',
    'first_name_placeholder' => 'Prénom',
    'last_name_ar_label' => 'Nom (en arabe)',
    'last_name_placeholder' => 'Nom',
    'phone_label' => 'Numéro de téléphone',
    'email_label' => 'Adresse e-mail',
    'birth_date_label' => 'Date de naissance',
    'password_label' => 'Mot de passe',
    'new_password_label' => 'Nouveau mot de passe',
    'password_confirm_label' => 'Confirmer le mot de passe',
    'otp_label' => 'Code de vérification',

    // ===== Login =====
    'login_title' => 'Connexion',
    'login_subtitle' => 'Saisissez vos informations pour accéder à votre compte Mazayada.',
    'forgot_password' => 'Mot de passe oublié ?',
    'login_button' => 'Se connecter',
    'no_account' => 'Vous n’avez pas de compte ?',
    'create_account_link' => 'Créer un nouveau compte',

    // ===== Register =====
    'register_title' => 'Créer un compte',
    // :terms and :privacy are replaced with links from the view.
    'terms_agree' => 'J’accepte les :terms et la :privacy de Mazayada.',
    'terms_link' => 'conditions d’utilisation',
    'privacy_link' => 'politique de confidentialité',
    'register_button' => 'Créer le compte',
    'have_account' => 'Vous avez déjà un compte ?',
    'login_link' => 'Se connecter',

    // ===== Verify OTP =====
    'otp_title' => 'Confirmer le code de vérification',
    'otp_subtitle' => 'Saisissez le code à 6 chiffres que nous avons envoyé à votre e-mail.',
    'otp_button' => 'Confirmer le code',
    'resend' => 'Renvoyer',
    'resend_hint' => 'Vous n’avez pas reçu le code ? Vérifiez votre e-mail ou votre dossier spam.',

    // ===== Reset password =====
    'reset_title' => 'Réinitialiser le mot de passe',
    'reset_subtitle_request' => 'Saisissez votre numéro d’identification nationale et votre e-mail pour recevoir un code de vérification.',
    'reset_subtitle_confirm' => 'Saisissez le code de vérification et votre nouveau mot de passe.',
    'change_password_button' => 'Changer le mot de passe',
    'send_otp_button' => 'Envoyer le code de vérification',
    'back_to_login' => 'Retour à la connexion',

    // ===== Controller flash / error messages =====
    'too_many_attempts' => 'Trop de tentatives échouées. Réessayez dans :sec secondes.',
    'account_locked' => 'Compte verrouillé. Réessayez :time',
    'invalid_credentials' => 'Identifiants de connexion incorrects.',
    'account_blocked' => 'Ce compte a été bloqué.',
    'otp_invalid' => 'Le code de vérification est incorrect ou a expiré.',
    'otp_resent' => 'Un nouveau code de vérification a été envoyé à votre e-mail.',
    'otp_resend_cooldown' => 'Veuillez patienter :sec secondes avant de demander un nouveau code.',
    'otp_too_many_attempts' => 'Trop de tentatives. Veuillez demander un nouveau code.',
    'verify_email_first' => 'Vous devez d’abord vérifier votre e-mail. Nous vous avons envoyé un nouveau code.',
    'account_not_found' => 'Compte introuvable.',
    'otp_sent' => 'Code de vérification envoyé.',
    'reset_otp_sent' => 'Si le compte existe, nous avons envoyé un code de vérification à votre e-mail.',
    'password_changed' => 'Votre mot de passe a été modifié avec succès.',
    'password_mismatch' => 'Le mot de passe fourni ne correspond pas à votre mot de passe actuel.',

    'logout' => 'Déconnexion',
    'lost_email_recover' => 'E-mail perdu ?',
    'recover_title' => 'Récupération du compte',
    'recover_subtitle_identify' => 'Saisissez votre NIN et votre e-mail pour afficher votre question secrète.',
    'recover_subtitle_answer' => 'Répondez à votre question secrète pour définir un nouveau mot de passe.',
    'recover_your_question' => 'Votre question secrète',
    'recover_answer_label' => 'Votre réponse',
    'recover_continue' => 'Continuer',
    'recover_use_email_instead' => 'Utiliser plutôt la récupération par e-mail',
    'recover_not_available' => 'La récupération par question secrète n\'est pas disponible pour ces informations. Utilisez la récupération par e-mail ou contactez le support.',
    'recover_wrong_answer' => 'Réponse incorrecte.',
    'recover_question_fallback' => 'Question secrète',
    'two_factor_title' => 'Activer l\'authentification à deux facteurs',
    'two_factor_intro' => 'Le personnel de la plateforme doit activer l\'A2F avant d\'accéder au tableau de bord.',
    'two_factor_required' => 'L\'authentification à deux facteurs est requise pour continuer.',
    'two_factor_enabled' => 'L\'authentification à deux facteurs est activée sur votre compte.',
    'two_factor_go_dashboard' => 'Aller au tableau de bord',
    'two_factor_scan' => 'Scannez le QR code avec votre application d\'authentification, puis saisissez le code à 6 chiffres.',
    'two_factor_code_label' => 'Code de vérification',
    'two_factor_confirm' => 'Confirmer l\'activation',
    'two_factor_cancel' => 'Annuler',
    'two_factor_enable_hint' => 'Vous aurez besoin d\'une application d\'authentification (ex. Google Authenticator).',
    'two_factor_enable' => 'Activer l\'A2F',

    'secret_questions' => [
        'mother_maiden' => 'Quel est le nom de jeune fille de votre mère ?',
        'first_school' => 'Quel est le nom de votre première école ?',
        'birth_city' => 'Dans quelle ville êtes-vous né(e) ?',
        'pet_name' => 'Quel est le nom de votre premier animal ?',
        'fav_teacher' => 'Qui est votre enseignant préféré ?',
    ],
];
