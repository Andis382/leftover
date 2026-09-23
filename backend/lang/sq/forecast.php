<?php

// Pse parashikimi sugjeron një numër. :when është "të hënën e kaluar" ose "më 8 shtator".
return [
    'reason' => [
        'baseline' => 'Ende pa numërime, prandaj kjo është sasia juaj e zakonshme.',
        'blended' => '{1} Vetëm një ditë e tillë e numëruar deri tani, e përzier me sasinë e zakonshme.|[2,*] Vetëm :count ditë të tilla të numëruara deri tani, të përziera me sasinë e zakonshme.',
        'soldOut' => 'Mbaroi në :time :when.',
        'soldOutOften' => 'Mbaroi para orës :before në :times nga :of :weekdays e fundit.',
        'soldOutLate' => 'Mbaroi në orën e fundit para mbylljes në :times nga :of :weekdays e fundit.',
        'leftEach' => 'Mbetën nga :left copë në secilën nga dy :weekdays e fundit.',
        'leftTwo' => 'Mbetën :recent dhe :before copë dy :weekdays e fundit.',
        'leftLast' => 'Mbetën :left copë :when.',
        'average' => 'Mesatarisht :sold të shitura :weekdays e fundit.',
        'steady' => 'U shitën :sold nga :baked :when.',
    ],
    'on_date' => 'më :date',
    'weekday' => [
        'name' => [1 => 'e hënë', 2 => 'e martë', 3 => 'e mërkurë', 4 => 'e enjte', 5 => 'e premte', 6 => 'e shtunë', 7 => 'e diel'],
        'last' => [1 => 'të hënën e kaluar', 2 => 'të martën e kaluar', 3 => 'të mërkurën e kaluar', 4 => 'të enjten e kaluar', 5 => 'të premten e kaluar', 6 => 'të shtunën e kaluar', 7 => 'të dielën e kaluar'],
        'on' => [1 => 'të hënën', 2 => 'të martën', 3 => 'të mërkurën', 4 => 'të enjten', 5 => 'të premten', 6 => 'të shtunën', 7 => 'të dielën'],
        'plural' => [1 => 'të hënat', 2 => 'të martat', 3 => 'të mërkurat', 4 => 'të enjtet', 5 => 'të premtet', 6 => 'të shtunat', 7 => 'të dielat'],
    ],
];
