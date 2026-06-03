<?php

return [

    // ===== Shared UI chrome for every legal page =====
    'ui' => [
        'home' => 'Home',
        'updated' => 'Last updated: :date',
        'updated_date' => 'June 2026',
        'related_title' => 'Related legal pages',
        'disclaimer' => 'This page is provided for informational and explanatory purposes and does not replace the official legal texts in force in Algeria. In the event of any conflict, the provisions of the relevant Algerian laws and decrees, together with each auction’s condition book, shall prevail.',
    ],

    // ===== Legal Framework — sources: spec §2.1, §2.3, §2.4 =====
    'framework' => [
        'eyebrow' => 'Legal basis',
        'title' => 'Legal Framework',
        'intro' => 'The Mazayada platform operates within a strict Algerian legal framework that governs every stage of public auctions, whether sale or lease. Below are the main laws and decrees the platform relies on, and the specific rules it applies.',
        'sections' => [
            [
                'title' => 'Core governing laws and decrees',
                'body' => 'The platform complies with the following texts, and every feature is designed to conform to them:',
                'points' => [
                    'Civil Code (Article 96): a sale contract is formed only upon the hammer fall (adjudication); there is no final commitment before the last bid is confirmed.',
                    'Code of Civil and Administrative Procedure 08-09 (Articles 710 to 762): defines the procedures for judicial sale by auction and the price settlement periods.',
                    'Law 13-23: makes the judicial officer (huissier) the sole public officer authorised to conduct and legally oversee auctions.',
                    'Law 18-07 and its amendment 25-11: personal data protection, including keeping data on servers located in Algeria.',
                    'Executive Decree 10-210: the 18-digit National Identification Number (NIN), the primary identifier for every account.',
                    'Law 15-04: electronic signature and certification; documents issued by the platform carry an electronic signature and a verification QR code.',
                    'Law 18-05: electronic commerce, to which all of the platform’s digital transactions are subject.',
                    'Article 341 bis of the Customs Code: expressly authorises electronic auctions for customs goods.',
                    'Law 90-30: national property, governing the sale and lease of public and private State assets.',
                    'Executive Decree 97-33: the judicial officer fee schedule, calculated automatically at the time of payment.',
                ],
            ],
            [
                'title' => 'Rules specific to customs auctions',
                'body' => 'Auctions of customs goods are subject to additional rules:',
                'points' => [
                    'The winner pays at least 20% of the hammer price as soon as they win a customs auction.',
                    'Professional (commercial) goods require the bidder to hold a valid Commercial Register.',
                    'Bidders may inspect the goods within 48 hours before the auction starts, with the asset location shown on the platform.',
                    'Electronic auctions are expressly authorised by Article 341 bis of the Customs Code.',
                ],
            ],
            [
                'title' => 'Municipal and State property rules',
                'body' => 'Municipal and State property auctions are subject to specific conditions:',
                'points' => [
                    'The condition book (cahier des charges) is legally mandatory and available for purchase before participating in the auction.',
                    'For assets worth more than 200,000 DZD, the announcement must be published in a national daily newspaper; the platform announcement supplements this publication and does not replace it.',
                    'Municipal auction authority rests with the President of the People’s Municipal Assembly or their deputy.',
                    'Lease auctions are usually for a term of 3 years, renewable twice — a term configurable for each auction.',
                ],
            ],
        ],
    ],

    // ===== Privacy Policy — sources: spec §9.1, §3.3 =====
    'privacy' => [
        'eyebrow' => 'Protecting your data',
        'title' => 'Privacy Policy',
        'intro' => 'At Mazayada, we are committed to protecting your personal data in accordance with Law 18-07 and its amendment 25-11 on the protection of natural persons in the processing of personal data. This page explains the data we collect and why, your rights, and how we protect your data.',
        'sections' => [
            [
                'title' => 'The data we collect and why',
                'body' => 'We collect — mainly as part of identity verification (KYC) — the data needed to verify your identity, prevent fraud and comply with legal obligations, including:',
                'points' => [
                    'Full first and last name in Arabic and French.',
                    'Father’s first name, and the mother’s first and last name.',
                    'Profession and full address (street, commune, wilaya, postal code).',
                    'Estimated monthly income range.',
                    'Postal account number (RIP) with Algérie Poste.',
                    'Images of identity documents and a personal photo (selfie) — biometric data handled with particular care.',
                ],
            ],
            [
                'title' => 'Your rights over your data',
                'body' => 'Law 18-07 grants you clear rights over your data, and we guarantee your ability to exercise them:',
                'points' => [
                    'Right of access: you may request a copy of all your personal data, provided within 30 days.',
                    'Right to erasure: you may request deletion of your account, after which your biometric data is permanently erased — not merely hidden.',
                    'Right to consent: your explicit consent is obtained at registration and logged with the timestamp and IP address.',
                ],
            ],
            [
                'title' => 'Data protection and localisation',
                'body' => 'We apply strict measures to protect your data:',
                'points' => [
                    'All data is stored and processed on servers located in Algeria; it is not transferred outside the national territory without authorisation from the National Data Protection Authority (ANPDP).',
                    'Sensitive and biometric data is encrypted and isolated from the main database.',
                    'We notify the National Data Protection Authority of any security breach within 72 hours of its discovery.',
                    'We have appointed a Data Protection Officer (DPO) to oversee compliance and handle your requests.',
                ],
            ],
            [
                'title' => 'Contact regarding privacy',
                'body' => 'For any question or request concerning your personal data, or to exercise your rights, you can contact the Data Protection Officer by email: contact@mazayada.dz.',
                'points' => [],
            ],
        ],
    ],

    // ===== Terms of Use — sources: spec §4 (steps 3, 7, 8), §6.3, §8.4 =====
    'terms' => [
        'eyebrow' => 'Terms & conditions',
        'title' => 'Terms of Use',
        'intro' => 'By using the Mazayada platform and participating in public auctions, you acknowledge that you have read and agreed to these terms. This page sets out your obligations at each stage of the auction and the rules for using your account.',
        'sections' => [
            [
                'title' => 'Auction registration and participation',
                'body' => 'Before bidding on any asset, you must register for the relevant auction and pay:',
                'points' => [
                    'A non-refundable entry fee.',
                    'A refundable security deposit (caution), held in the entity’s escrow account.',
                    'Once payment is complete, you become a registered bidder and may submit bids.',
                ],
            ],
            [
                'title' => 'Final payment upon winning',
                'body' => 'If you win the auction, you must complete the final payment within the legal deadline:',
                'points' => [
                    '8 days for movable assets.',
                    '15 days for real estate.',
                    'The remaining amount (final price minus the deposit already paid) is payable, plus any due fees.',
                ],
            ],
            [
                'title' => 'Breach of obligations and the blacklist',
                'body' => 'Breaching payment obligations carries specific consequences:',
                'points' => [
                    'If you win the auction but do not pay within the deadline, your deposit is forfeited to the entity’s account and your account is added to the blacklist.',
                    'For a compliant winner, the deposit is deducted from the total amount.',
                    'As for non-winning bidders, their deposits are refunded automatically after the auction closes, unless a violation occurs.',
                ],
            ],
            [
                'title' => 'Bidding mechanism and auto-extension',
                'body' => 'The live auction runs with an automatic extension mechanism to ensure fairness and prevent last-second monopolisation:',
                'points' => [
                    'If a bid is submitted during the final seconds of the auction (30 seconds by default), the auction time is automatically extended (5 minutes by default).',
                    'This is meant to prevent placing a winning bid at the last second to shut out counter-bids.',
                    'These values are configurable by the platform administration or the entity for each auction.',
                ],
            ],
            [
                'title' => 'Account, security and service suspension',
                'body' => 'To preserve the platform’s security and fairness, the following policies apply to accounts:',
                'points' => [
                    'After 5 failed login attempts, the account is temporarily locked for 15 minutes.',
                    'A maximum of 10 bids per minute is allowed per user per auction.',
                    'An account may be suspended or banned in case of violations, or if identity verification (KYC) is not completed within the set deadline.',
                ],
            ],
        ],
    ],

    // ===== Legal Notices — sources: spec §2.2, §10.3, §10.2 =====
    'notices' => [
        'eyebrow' => 'Important notices',
        'title' => 'Legal Notices',
        'intro' => 'This page contains important legal notices and warnings that every user should review before participating in auctions; they concern fees, the condition book, and the documents issued by the platform.',
        'sections' => [
            [
                'title' => 'Additional fees borne by the buyer',
                'body' => 'Added to the final auction price — and on top of value-added tax (VAT) — is a set of legal fees set by Executive Decree 97-33, including:',
                'points' => [
                    'Proportional right borne by the buyer: 3% of the sale price.',
                    'Proportional right borne by the seller: 5% of the sale price.',
                    'Hammer (adjudication) fee: a tiered rate based on the sale value (from 1.5% to 6%).',
                    'These fees are itemised on the payment invoice before it is confirmed.',
                ],
            ],
            [
                'title' => 'The condition book is non-refundable',
                'body' => 'Important warning regarding the condition book:',
                'points' => [
                    'The condition book purchase fee is non-refundable under any circumstances.',
                    'You must acknowledge having read the condition book (via a timestamped confirmation checkbox) before you can register for the auction.',
                ],
            ],
            [
                'title' => 'The award document is legally binding',
                'body' => 'Regarding the documents issued by the platform:',
                'points' => [
                    'The electronic award document issued by the platform is a legally binding document.',
                    'It carries an official electronic signature compliant with Law 15-04, along with a QR code that allows its authenticity to be verified.',
                    'Ownership of the asset transfers after full payment, in accordance with the condition book and the laws in force.',
                ],
            ],
        ],
    ],

];
