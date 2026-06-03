<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Messages de validation
    |--------------------------------------------------------------------------
    |
    | La structure des clés doit rester identique à en/ar — le test
    | LocalizationTest le vérifie.
    |
    */

    'accepted' => 'Le champ :attribute doit être accepté.',
    'active_url' => 'Le champ :attribute doit être une URL valide.',
    'after' => 'Le champ :attribute doit être une date postérieure à :date.',
    'after_or_equal' => 'Le champ :attribute doit être une date postérieure ou égale à :date.',
    'alpha' => 'Le champ :attribute ne doit contenir que des lettres.',
    'alpha_dash' => 'Le champ :attribute ne doit contenir que des lettres, des chiffres, des tirets et des underscores.',
    'alpha_num' => 'Le champ :attribute ne doit contenir que des lettres et des chiffres.',
    'array' => 'Le champ :attribute doit être un tableau.',
    'before' => 'Le champ :attribute doit être une date antérieure à :date.',
    'before_or_equal' => 'Le champ :attribute doit être une date antérieure ou égale à :date.',
    'between' => [
        'array' => 'Le champ :attribute doit contenir entre :min et :max éléments.',
        'file' => 'Le champ :attribute doit être compris entre :min et :max kilo-octets.',
        'numeric' => 'Le champ :attribute doit être compris entre :min et :max.',
        'string' => 'Le champ :attribute doit comporter entre :min et :max caractères.',
    ],
    'boolean' => 'Le champ :attribute doit être vrai ou faux.',
    'confirmed' => 'La confirmation du champ :attribute ne correspond pas.',
    'current_password' => 'Le mot de passe est incorrect.',
    'date' => 'Le champ :attribute doit être une date valide.',
    'date_equals' => 'Le champ :attribute doit être une date égale à :date.',
    'date_format' => 'Le champ :attribute doit respecter le format :format.',
    'different' => 'Les champs :attribute et :other doivent être différents.',
    'digits' => 'Le champ :attribute doit comporter :digits chiffres.',
    'digits_between' => 'Le champ :attribute doit comporter entre :min et :max chiffres.',
    'email' => 'Le champ :attribute doit être une adresse e-mail valide.',
    'ends_with' => 'Le champ :attribute doit se terminer par l’une des valeurs suivantes : :values.',
    'exists' => 'La valeur sélectionnée pour :attribute est invalide.',
    'file' => 'Le champ :attribute doit être un fichier.',
    'filled' => 'Le champ :attribute doit avoir une valeur.',
    'gt' => [
        'array' => 'Le champ :attribute doit contenir plus de :value éléments.',
        'file' => 'Le champ :attribute doit être supérieur à :value kilo-octets.',
        'numeric' => 'Le champ :attribute doit être supérieur à :value.',
        'string' => 'Le champ :attribute doit comporter plus de :value caractères.',
    ],
    'gte' => [
        'array' => 'Le champ :attribute doit contenir :value éléments ou plus.',
        'file' => 'Le champ :attribute doit être supérieur ou égal à :value kilo-octets.',
        'numeric' => 'Le champ :attribute doit être supérieur ou égal à :value.',
        'string' => 'Le champ :attribute doit comporter au moins :value caractères.',
    ],
    'image' => 'Le champ :attribute doit être une image.',
    'in' => 'La valeur sélectionnée pour :attribute est invalide.',
    'integer' => 'Le champ :attribute doit être un entier.',
    'lt' => [
        'array' => 'Le champ :attribute doit contenir moins de :value éléments.',
        'file' => 'Le champ :attribute doit être inférieur à :value kilo-octets.',
        'numeric' => 'Le champ :attribute doit être inférieur à :value.',
        'string' => 'Le champ :attribute doit comporter moins de :value caractères.',
    ],
    'lte' => [
        'array' => 'Le champ :attribute ne doit pas contenir plus de :value éléments.',
        'file' => 'Le champ :attribute doit être inférieur ou égal à :value kilo-octets.',
        'numeric' => 'Le champ :attribute doit être inférieur ou égal à :value.',
        'string' => 'Le champ :attribute doit comporter au plus :value caractères.',
    ],
    'max' => [
        'array' => 'Le champ :attribute ne doit pas contenir plus de :max éléments.',
        'file' => 'Le champ :attribute ne doit pas dépasser :max kilo-octets.',
        'numeric' => 'Le champ :attribute ne doit pas être supérieur à :max.',
        'string' => 'Le champ :attribute ne doit pas dépasser :max caractères.',
    ],
    'min' => [
        'array' => 'Le champ :attribute doit contenir au moins :min éléments.',
        'file' => 'Le champ :attribute doit faire au moins :min kilo-octets.',
        'numeric' => 'Le champ :attribute doit être au moins :min.',
        'string' => 'Le champ :attribute doit comporter au moins :min caractères.',
    ],
    'not_in' => 'La valeur sélectionnée pour :attribute est invalide.',
    'numeric' => 'Le champ :attribute doit être un nombre.',
    'password' => [
        'letters' => 'Le champ :attribute doit contenir au moins une lettre.',
        'mixed' => 'Le champ :attribute doit contenir au moins une majuscule et une minuscule.',
        'numbers' => 'Le champ :attribute doit contenir au moins un chiffre.',
        'symbols' => 'Le champ :attribute doit contenir au moins un symbole.',
        'uncompromised' => 'Le :attribute fourni est apparu dans une fuite de données. Veuillez choisir un :attribute différent.',
    ],
    'present' => 'Le champ :attribute doit être présent.',
    'regex' => 'Le format du champ :attribute est invalide.',
    'required' => 'Le champ :attribute est obligatoire.',
    'required_if' => 'Le champ :attribute est obligatoire lorsque :other est :value.',
    'required_unless' => 'Le champ :attribute est obligatoire sauf si :other est dans :values.',
    'required_with' => 'Le champ :attribute est obligatoire lorsque :values est présent.',
    'required_without' => 'Le champ :attribute est obligatoire lorsque :values n’est pas présent.',
    'same' => 'Les champs :attribute et :other doivent correspondre.',
    'size' => [
        'array' => 'Le champ :attribute doit contenir :size éléments.',
        'file' => 'Le champ :attribute doit faire :size kilo-octets.',
        'numeric' => 'Le champ :attribute doit être :size.',
        'string' => 'Le champ :attribute doit comporter :size caractères.',
    ],
    'starts_with' => 'Le champ :attribute doit commencer par l’une des valeurs suivantes : :values.',
    'string' => 'Le champ :attribute doit être une chaîne de caractères.',
    'unique' => 'La valeur du champ :attribute est déjà utilisée.',
    'uploaded' => 'Le téléversement du champ :attribute a échoué.',
    'url' => 'Le champ :attribute doit être une URL valide.',

    /*
    |--------------------------------------------------------------------------
    | Noms des champs affichés dans les messages ci-dessus.
    |--------------------------------------------------------------------------
    */
    'attributes' => [
        'nin' => 'numéro d’identification nationale',
        'nin_or_email' => 'numéro d’identification nationale ou e-mail',
        'first_name_ar' => 'prénom (en arabe)',
        'last_name_ar' => 'nom (en arabe)',
        'phone' => 'numéro de téléphone',
        'email' => 'adresse e-mail',
        'birth_date' => 'date de naissance',
        'password' => 'mot de passe',
        'password_confirmation' => 'confirmation du mot de passe',
        'otp' => 'code de vérification',
        'terms' => 'conditions d’utilisation',
    ],
];
