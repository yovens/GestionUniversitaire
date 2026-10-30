<?php

return [
    'etablissement' => [
        'nom' => env('ITAC_NOM', 'Institut de Technologies Appliquées des Cayes'),
        'sigle' => env('ITAC_SIGLE', 'ITAC'),
        'adresse' => env('ITAC_ADRESSE', 'Les Cayes, Haïti'),
        'telephone' => env('ITAC_TELEPHONE', ''),
        'email' => env('ITAC_EMAIL', ''),
    ],

    'devise' => 'HTG',

    // Calcul de la moyenne d'un cours : intra et final pondérés (en %).
    // La note de reprise remplace la moyenne du cours si elle est meilleure.
    'notes' => [
        'max' => 100,
        'poids_intra' => (float) env('ITAC_POIDS_INTRA', 40),
        'poids_final' => (float) env('ITAC_POIDS_FINAL', 60),
    ],

    'uploads' => [
        'photo_max_kb' => 2048,
        'document_max_kb' => 5120,
    ],
];
