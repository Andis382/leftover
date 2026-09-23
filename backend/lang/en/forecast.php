<?php

// Why the forecast suggests a number. :when is "last Monday" or "on 8 Sep".
return [
    'reason' => [
        'baseline' => 'No counts yet, so this is your usual number.',
        'blended' => '{1} Only one counted :name so far, mixed with your usual number.|[2,*] Only :count counted :weekdays so far, mixed with your usual number.',
        'soldOut' => 'Sold out by :time :when.',
        'soldOutOften' => 'Sold out before :before on :times of the last :of :weekdays.',
        'soldOutLate' => 'Sold out in the last hour on :times of the last :of :weekdays.',
        'leftEach' => ':left left over on each of the last two :weekdays.',
        'leftTwo' => ':recent and :before left over on the last two :weekdays.',
        'leftLast' => ':left left over :when.',
        'average' => 'Averaging :sold sold on recent :weekdays.',
        'steady' => 'Sold :sold of :baked :when.',
    ],
    'on_date' => 'on :date',
    'weekday' => [
        'name' => [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'],
        'last' => [1 => 'last Monday', 2 => 'last Tuesday', 3 => 'last Wednesday', 4 => 'last Thursday', 5 => 'last Friday', 6 => 'last Saturday', 7 => 'last Sunday'],
        'on' => [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'],
        'plural' => [1 => 'Mondays', 2 => 'Tuesdays', 3 => 'Wednesdays', 4 => 'Thursdays', 5 => 'Fridays', 6 => 'Saturdays', 7 => 'Sundays'],
    ],
];
