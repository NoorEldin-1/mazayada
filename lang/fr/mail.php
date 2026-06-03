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
];
