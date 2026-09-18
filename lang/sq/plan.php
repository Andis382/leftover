<?php

/*
 * Çdo fjalë që thotë plani.
 *
 * Tri rregulla, dhe tests/Unit/PlanToneTest.php ia mban fjalën.
 *
 *   1. Ndryshimi thuhet si ndryshim dhe pastaj si total: "pesë kruasanë më pak,
 *      pra dyzet e pesë". Askush nuk kthen përqindje në orën katër të mëngjesit.
 *   2. Është shtysë, kurrë urdhër. Pa "duhet", pa "optimale". Është këshillë nga
 *      një bllok shënimesh, dhe furrtari di gjëra që blloku nuk i di.
 *   3. Nuk qorton kurrë. Mbetja thuhet si fakt, jo si faj.
 */

return [
    'heading' => 'Plani i pjekjes për :weekday :date',

    'section' => [
        'changes' => 'Ia vlen të ndryshohet:',
        'same' => 'Si zakonisht:',
        'unknown' => 'Ende pa mjaft numërime:',
    ],

    'line' => [
        'more' => '{1} 1 :product më shumë, pra :total|[2,*] :count :product më shumë, pra :total',
        'fewer' => '{1} 1 :product më pak, pra :total|[2,*] :count :product më pak, pra :total',
        'same' => ':product :count, njësoj',
        'unknown' => ':product — ende pa mjaft ditë të numëruara',
    ],

    'reason' => [
        'not_enough_yet' => 'Me dy ditë të numëruara të kësaj dite fillon të sugjerojë.',
        'left_over' => 'Rreth :left mbeteshin çdo herë.',
        'demand_down' => 'Po shitet pak më ngadalë kohët e fundit.',
        'demand_up' => 'Po shitet pak më shpejt kohët e fundit.',
        'sold_out_often' => 'U shitën krejt në :days prej tyre.',
        'sold_out_sometimes' => 'U shitën krejt :days herë.|U shitën krejt :days herë.',
        'steady' => 'I qëndrueshëm.',
        'steady_with_waste' => 'I qëndrueshëm, me rreth :left të mbetura çdo herë.',
    ],

    'confidence' => [
        'none' => 'ende pa sugjerim',
        'low' => 'i hollë',
        'fair' => 'i arsyeshëm',
        'good' => 'i qëndrueshëm',
    ],

    'footer' => 'Këto janë sugjerime nga numërimet e tua. Ti di gjëra që ato nuk i dinë.',
];
