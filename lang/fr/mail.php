<?php

return [
    // ===== OTP verification email (registration) =====
    'otp' => [
        'subject' => 'Code de vérification de votre e-mail — Mazayada',
        'greeting' => 'Bonjour :name,',
        'intro' => 'Utilisez le code suivant pour confirmer votre adresse e-mail et finaliser la création de votre compte Mazayada :',
        'code_label' => 'Code de vérification',
        'expiry' => 'Ce code n’est valable que pendant :minutes minutes.',
        'ignore' => 'Si vous n’avez pas demandé ce code, vous pouvez ignorer ce message en toute sécurité.',
        'footer' => 'Ceci est un message automatique de Mazayada, merci de ne pas y répondre.',
    ],

    // ===== OTP email (password reset) =====
    'reset' => [
        'subject' => 'Votre code de réinitialisation de mot de passe — Mazayada',
        'greeting' => 'Bonjour :name,',
        'intro' => 'Utilisez le code suivant pour réinitialiser le mot de passe de votre compte Mazayada :',
        'code_label' => 'Code de vérification',
        'expiry' => 'Ce code n’est valable que pendant :minutes minutes.',
        'ignore' => 'Si vous n’avez pas demandé de réinitialisation de mot de passe, vous pouvez ignorer ce message en toute sécurité.',
        'footer' => 'Ceci est un message automatique de Mazayada, merci de ne pas y répondre.',
    ],

    // ===== KYC decision emails =====
    'kyc_approved' => [
        'subject' => 'Votre identité est vérifiée — Mazayada',
        'greeting' => 'Bonjour :name,',
        'intro' => 'Nous avons le plaisir de vous informer que votre demande de vérification d’identité a été approuvée. Vous pouvez désormais participer aux enchères sur Mazayada.',
        'cta' => 'Accéder à mon compte',
        'footer' => 'Ceci est un message automatique de Mazayada, merci de ne pas y répondre.',
    ],
    'kyc_rejected' => [
        'subject' => 'Demande de vérification d’identité rejetée — Mazayada',
        'greeting' => 'Bonjour :name,',
        'intro' => 'Nous regrettons de vous informer que votre demande de vérification d’identité a été rejetée. Vous pouvez corriger vos informations et renvoyer la demande.',
        'reason_label' => 'Motif du rejet',
        'cta' => 'Corriger la demande',
        'footer' => 'Ceci est un message automatique de Mazayada, merci de ne pas y répondre.',
    ],
    'kyc_suspended' => [
        'subject' => 'Votre compte a été suspendu — Mazayada',
        'greeting' => 'Bonjour :name,',
        'intro' => 'Votre compte a été suspendu car la vérification d’identité n’a pas été complétée dans le délai imparti. Complétez-la pour réactiver votre compte.',
        'cta' => 'Compléter la vérification',
        'footer' => 'Ceci est un message automatique de Mazayada, merci de ne pas y répondre.',
    ],
];
