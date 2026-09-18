<?php

use Illuminate\Support\Facades\Schedule;

/**
 * Hourly, not at four in the morning.
 *
 * Each shop carries its own plan hour and its own timezone, and the command
 * serves a shop only when that shop's local clock has reached it. One line here
 * therefore covers a bakery in Tirana and one in Hamburg, and a shop that moves
 * its plan to half past three does not need anyone to touch a crontab.
 */
Schedule::command('leftover:plan')->hourly()->withoutOverlapping();
