<?php

return [

    // ===== Shared UI chrome for every legal page =====
    'ui' => [
        'home' => 'Accueil',
        'updated' => 'Dernière mise à jour : :date',
        'updated_date' => 'Juin 2026',
        'related_title' => 'Pages juridiques connexes',
        'disclaimer' => 'Cette page est fournie à titre informatif et explicatif ; elle ne remplace pas les textes juridiques officiels en vigueur en Algérie. En cas de divergence, les dispositions des lois et décrets algériens applicables ainsi que le cahier des charges de chaque enchère prévalent.',
    ],

    // ===== Legal Framework — sources: spec §2.1, §2.3, §2.4 =====
    'framework' => [
        'eyebrow' => 'Fondement juridique',
        'title' => 'Cadre juridique',
        'intro' => 'La plateforme Mazayada opère dans un cadre juridique algérien strict qui régit chaque étape des enchères publiques, qu’il s’agisse de vente ou de location. Voici les principales lois et décrets sur lesquels la plateforme s’appuie, ainsi que les règles particulières qu’elle applique.',
        'sections' => [
            [
                'title' => 'Lois et décrets fondamentaux',
                'body' => 'La plateforme se conforme aux textes suivants, et chacune de ses fonctionnalités est conçue pour les respecter :',
                'points' => [
                    'Code civil (article 96) : le contrat de vente n’est conclu qu’au moment de l’adjudication (coup de marteau) ; aucun engagement définitif avant la confirmation de la dernière offre.',
                    'Code de procédure civile et administrative 08-09 (articles 710 à 762) : définit les procédures de vente judiciaire aux enchères et les délais de règlement du prix.',
                    'Loi 13-23 : fait de l’huissier de justice le seul officier public habilité à conduire et superviser légalement les enchères.',
                    'Loi 18-07 et son amendement 25-11 : protection des données personnelles, y compris le maintien des données sur des serveurs situés en Algérie.',
                    'Décret exécutif 10-210 : le numéro d’identification national (NIN) à 18 chiffres, identifiant principal de chaque compte.',
                    'Loi 15-04 : signature et certification électroniques ; les documents de la plateforme portent une signature électronique et un code QR de vérification.',
                    'Loi 18-05 : commerce électronique auquel sont soumises toutes les transactions numériques de la plateforme.',
                    'Article 341 bis du Code des douanes : autorise expressément la vente aux enchères électroniques des marchandises douanières.',
                    'Loi 90-30 : domaine national, régissant la vente et la location des biens publics et privés de l’État.',
                    'Décret exécutif 97-33 : barème des honoraires de l’huissier de justice, calculés automatiquement au moment du paiement.',
                ],
            ],
            [
                'title' => 'Règles propres aux enchères douanières',
                'body' => 'Les enchères de marchandises douanières sont soumises à des règles supplémentaires :',
                'points' => [
                    'L’adjudicataire verse au minimum 20 % du prix d’adjudication dès qu’il remporte une enchère douanière.',
                    'Les marchandises professionnelles (commerciales) exigent que l’enchérisseur détienne un registre de commerce en cours de validité.',
                    'Les enchérisseurs peuvent inspecter les marchandises dans les 48 heures précédant le début de l’enchère, l’emplacement du bien étant affiché sur la plateforme.',
                    'La vente aux enchères électroniques est expressément autorisée par l’article 341 bis du Code des douanes.',
                ],
            ],
            [
                'title' => 'Règles des communes et du domaine de l’État',
                'body' => 'Les enchères des communes et du domaine de l’État sont soumises à des conditions particulières :',
                'points' => [
                    'Le cahier des charges est légalement obligatoire et disponible à l’achat avant la participation à l’enchère.',
                    'Pour les biens dont la valeur dépasse 200 000 DZD, l’annonce doit être publiée dans un quotidien national ; l’annonce de la plateforme complète cette publication sans la remplacer.',
                    'Le pouvoir d’adjudication communal revient au président de l’Assemblée populaire communale ou à son suppléant.',
                    'Les enchères de location portent généralement sur une durée de 3 ans renouvelable deux fois, durée paramétrable pour chaque enchère.',
                ],
            ],
        ],
    ],

    // ===== Privacy Policy — sources: spec §9.1, §3.3 =====
    'privacy' => [
        'eyebrow' => 'Protection de vos données',
        'title' => 'Politique de confidentialité',
        'intro' => 'Chez Mazayada, nous nous engageons à protéger vos données personnelles conformément à la loi 18-07 et à son amendement 25-11 relatifs à la protection des personnes physiques dans le traitement des données à caractère personnel. Cette page précise les données que nous collectons et pourquoi, vos droits, et la manière dont nous protégeons vos données.',
        'sections' => [
            [
                'title' => 'Les données que nous collectons et pourquoi',
                'body' => 'Nous collectons — principalement dans le cadre de la vérification d’identité (KYC) — les données nécessaires pour vérifier votre identité, prévenir la fraude et respecter les obligations légales, à savoir :',
                'points' => [
                    'Nom et prénom complets en arabe et en français.',
                    'Prénom du père, prénom et nom de la mère.',
                    'Profession et adresse complète (rue, commune, wilaya, code postal).',
                    'Tranche de revenu mensuel estimé.',
                    'Numéro de compte postal (RIP) auprès d’Algérie Poste.',
                    'Images des pièces d’identité et photo personnelle (selfie), données biométriques traitées avec un soin particulier.',
                ],
            ],
            [
                'title' => 'Vos droits sur vos données',
                'body' => 'La loi 18-07 vous accorde des droits clairs sur vos données, et nous vous en garantissons l’exercice :',
                'points' => [
                    'Droit d’accès : vous pouvez demander une copie de toutes vos données personnelles, fournie sous 30 jours.',
                    'Droit à l’effacement : vous pouvez demander la suppression de votre compte ; vos données biométriques sont alors définitivement effacées, et non simplement masquées.',
                    'Droit au consentement : votre consentement explicite est recueilli lors de l’inscription et enregistré avec l’horodatage et l’adresse IP.',
                ],
            ],
            [
                'title' => 'Protection et localisation des données',
                'body' => 'Nous appliquons des mesures strictes pour protéger vos données :',
                'points' => [
                    'Toutes les données sont stockées et traitées sur des serveurs situés en Algérie ; elles ne sont pas transférées hors du territoire national sans autorisation de l’Autorité nationale de protection des données (ANPDP).',
                    'Les données sensibles et biométriques sont chiffrées et isolées de la base de données principale.',
                    'Nous notifions l’Autorité nationale de protection des données de toute violation de sécurité dans les 72 heures suivant sa découverte.',
                    'Nous avons désigné un délégué à la protection des données (DPO) pour veiller à la conformité et suivre vos demandes.',
                ],
            ],
            [
                'title' => 'Contact concernant la confidentialité',
                'body' => 'Pour toute question ou demande relative à vos données personnelles, ou pour exercer vos droits, vous pouvez contacter le délégué à la protection des données par e-mail : contact@mazayada.dz.',
                'points' => [],
            ],
        ],
    ],

    // ===== Terms of Use — sources: spec §4 (steps 3, 7, 8), §6.3, §8.4 =====
    'terms' => [
        'eyebrow' => 'Conditions générales',
        'title' => 'Conditions d’utilisation',
        'intro' => 'En utilisant la plateforme Mazayada et en participant aux enchères publiques, vous reconnaissez avoir lu et accepté les présentes conditions. Cette page précise vos obligations à chaque étape de l’enchère ainsi que les règles d’utilisation du compte.',
        'sections' => [
            [
                'title' => 'Inscription à l’enchère et participation',
                'body' => 'Avant d’enchérir sur un bien, vous devez vous inscrire à l’enchère concernée et payer :',
                'points' => [
                    'Des frais d’entrée non remboursables.',
                    'Un montant de garantie (caution) remboursable, retenu sur le compte séquestre de l’entité gouvernementale.',
                    'Une fois le paiement effectué, vous devenez un enchérisseur inscrit et pouvez soumettre des offres.',
                ],
            ],
            [
                'title' => 'Paiement final en cas de gain',
                'body' => 'Si vous remportez l’enchère, vous devez effectuer le paiement final dans le délai légal :',
                'points' => [
                    '8 jours pour les biens mobiliers.',
                    '15 jours pour les biens immobiliers.',
                    'Le montant restant (prix final moins la caution versée) est payé, majoré des frais dus.',
                ],
            ],
            [
                'title' => 'Manquement aux obligations et liste noire',
                'body' => 'Le manquement aux obligations de paiement entraîne des conséquences précises :',
                'points' => [
                    'Si vous remportez l’enchère sans payer dans le délai, votre caution est confisquée au profit du compte de l’entité et votre compte est inscrit sur la liste noire.',
                    'Pour l’adjudicataire qui s’acquitte de ses obligations, la caution est déduite du montant total.',
                    'Quant aux enchérisseurs non gagnants, leurs cautions sont remboursées automatiquement après la clôture de l’enchère, sauf infraction.',
                ],
            ],
            [
                'title' => 'Mécanisme d’enchère et prolongation automatique',
                'body' => 'L’enchère en direct fonctionne avec un mécanisme de prolongation automatique pour garantir l’équité et empêcher le monopole des dernières secondes :',
                'points' => [
                    'Si une offre est soumise durant les dernières secondes de l’enchère (30 secondes par défaut), la durée de l’enchère est automatiquement prolongée (5 minutes par défaut).',
                    'Cela vise à empêcher la soumission d’une offre gagnante à la dernière seconde pour écarter les contre-offres.',
                    'Ces valeurs sont paramétrables par l’administration de la plateforme ou par l’entité pour chaque enchère.',
                ],
            ],
            [
                'title' => 'Compte, sécurité et suspension du service',
                'body' => 'Afin de préserver la sécurité et l’équité de la plateforme, les politiques suivantes s’appliquent aux comptes :',
                'points' => [
                    'Après 5 tentatives de connexion infructueuses, le compte est temporairement verrouillé pendant 15 minutes.',
                    'Un maximum de 10 offres par minute est autorisé par utilisateur et par enchère.',
                    'Le compte peut être suspendu ou banni en cas d’infractions, ou si la vérification d’identité (KYC) n’est pas achevée dans le délai imparti.',
                ],
            ],
        ],
    ],

    // ===== Legal Notices — sources: spec §2.2, §10.3, §10.2 =====
    'notices' => [
        'eyebrow' => 'Avis importants',
        'title' => 'Mentions légales',
        'intro' => 'Cette page contient des mentions et avertissements juridiques importants que chaque utilisateur doit consulter avant de participer aux enchères ; ils concernent les frais, le cahier des charges et les documents émis par la plateforme.',
        'sections' => [
            [
                'title' => 'Frais supplémentaires à la charge de l’acheteur',
                'body' => 'Au prix final de l’enchère — et au-delà de la taxe sur la valeur ajoutée (TVA) — s’ajoute un ensemble de frais légaux fixés par le décret exécutif 97-33, notamment :',
                'points' => [
                    'Droit proportionnel à la charge de l’acheteur : 3 % du prix de vente.',
                    'Droit proportionnel à la charge du vendeur : 5 % du prix de vente.',
                    'Droit d’adjudication (coup de marteau) : taux dégressif selon la valeur de la vente (de 1,5 % à 6 %).',
                    'Ces frais sont détaillés sur la facture de paiement avant sa confirmation.',
                ],
            ],
            [
                'title' => 'Le cahier des charges n’est pas remboursable',
                'body' => 'Avertissement important concernant le cahier des charges :',
                'points' => [
                    'Les frais d’achat du cahier des charges ne sont remboursables en aucune circonstance.',
                    'Il faut attester avoir lu le cahier des charges (via une case de confirmation horodatée) avant de pouvoir s’inscrire à l’enchère.',
                ],
            ],
            [
                'title' => 'Le procès-verbal d’adjudication est juridiquement contraignant',
                'body' => 'Concernant les documents émis par la plateforme :',
                'points' => [
                    'Le procès-verbal d’adjudication électronique émis par la plateforme est un document juridiquement contraignant.',
                    'Il porte une signature électronique officielle conforme à la loi 15-04, ainsi qu’un code QR permettant de vérifier son authenticité.',
                    'La propriété du bien est transférée après le paiement intégral, conformément au cahier des charges et aux lois en vigueur.',
                ],
            ],
        ],
    ],

];
