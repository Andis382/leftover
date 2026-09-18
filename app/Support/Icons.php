<?php

namespace App\Support;

/**
 * The icon set, loaded once per request.
 *
 * Phosphor's regular weight, MIT licensed, copied into resources/icons.php as
 * raw path data so that nothing has to be installed, downloaded or built. An
 * icon is drawn on a 256 unit grid and inherits currentColor, which is the
 * whole point: a status icon has to be able to take its colour from the token
 * that already decides whether that status is calm, late or wrong.
 */
class Icons
{
    /** @var array<string, string>|null */
    private static ?array $set = null;

    /** @return array<string, string> */
    public static function all(): array
    {
        if (self::$set === null) {
            self::$set = require resource_path('icons.php');
        }

        return self::$set;
    }

    public static function has(string $name): bool
    {
        return isset(self::all()[$name]);
    }

    /** The inner SVG of one icon, or an empty string for a name we do not ship. */
    public static function body(string $name): string
    {
        return self::all()[$name] ?? '';
    }
}
