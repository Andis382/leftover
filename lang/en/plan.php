<?php

/*
 * Every word the plan says.
 *
 * Three rules, and tests/Unit/PlanToneTest.php holds them to it.
 *
 *   1. A change is stated as a change and then as a total: "five fewer
 *      croissants, so forty-five". Nobody converts a percentage at 4 a.m.
 *   2. It is a nudge, never an instruction. No "must", no "should", no
 *      "optimal". It is advice from a notebook, and the baker knows things the
 *      notebook does not — a funeral, a school trip, rain since six.
 *   3. It never scolds. Waste is stated as a fact and never as a failure;
 *      "you threw away seven" is information, "you wasted seven again" is an
 *      accusation, and the second one gets the app deleted in week three.
 */

return [
    'heading' => 'Bake plan for :weekday :date',

    'section' => [
        'changes' => 'Worth changing:',
        'same' => 'Same as usual:',
        'unknown' => 'Not enough counts yet:',
    ],

    'line' => [
        'more' => '{1} 1 more :product, so :total|[2,*] :count more :product, so :total',
        'fewer' => '{1} 1 fewer :product, so :total|[2,*] :count fewer :product, so :total',
        'same' => ':product :count, the same',
        'unknown' => ':product — not enough counted days yet',
    ],

    'reason' => [
        'not_enough_yet' => 'Two counted days of this weekday and it starts suggesting.',
        'left_over' => 'About :left left over each time.',
        'demand_down' => 'Selling a little slower lately.',
        'demand_up' => 'Selling a little faster lately.',
        'sold_out_often' => 'Sold out on :days of them.',
        'sold_out_sometimes' => 'Sold out :days time.|Sold out :days times.',
        'steady' => 'Steady.',
        'steady_with_waste' => 'Steady, with about :left left each time.',
    ],

    'confidence' => [
        'none' => 'no suggestion yet',
        'low' => 'thin',
        'fair' => 'fair',
        'good' => 'solid',
    ],

    'footer' => 'These are suggestions from your own counts. You know things they do not.',
];
