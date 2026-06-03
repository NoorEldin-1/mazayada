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
    'otp_subtitle' => 'Saisissez le code à 6 chiffres que nous avons envoyé à votre téléphone ou e-mail.',
    'otp_button' => 'Confirmer le code',
    'resend' => 'Renvoyer',
    'resend_hint' => 'Vous n’avez pas reçu le code ? Vérifiez votre numéro de téléphone ou votre e-mail.',

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
    'account_not_found' => 'Compte introuvable.',
    'otp_sent' => 'Code de vérification envoyé.',
    'password_changed' => 'Votre mot de passe a été modifié avec succès.',
    'password_mismatch' => 'Le mot de passe fourni ne correspond pas à votre mot de passe actuel.',
];
