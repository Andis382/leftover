#!/usr/bin/env python3
"""Extract the Phosphor regular-weight icons this project uses into a PHP map.

Phosphor Icons is MIT licensed (c) 2023 Phosphor Icons. Only the handful of
glyphs the interface actually references are copied, so the app keeps its
no-build-step promise: the SVG path data ships as plain PHP.
"""
import os
import re
import sys

SRC = "/tmp/phos/node_modules/@phosphor-icons/core/assets/regular"

# key in the app -> phosphor file name
WANTED = {
    # navigation
    "home": "house",
    "calendar": "calendar-dots",
    "plus": "plus",
    "outbox": "paper-plane-tilt",
    "book": "notebook",
    "settings": "gear-six",
    # appliance types
    "flame": "fire",
    "snowflake": "snowflake",
    "heat-pump": "thermometer-hot",
    "shower": "shower",
    "sun": "sun",
    "solar": "solar-panel",
    "bell": "bell-ringing",
    "fan": "fan",
    "wrench": "wrench",
    # status and actions
    "check": "check",
    "check-circle": "check-circle",
    "warning": "warning",
    "warning-circle": "warning-circle",
    "clock": "clock",
    "hourglass": "hourglass-medium",
    "phone": "phone",
    "whatsapp": "whatsapp-logo",
    "chat": "chat-circle-text",
    "sms": "chat-text",
    "copy": "copy",
    "printer": "printer",
    "pin": "map-pin",
    "camera": "camera",
    "search": "magnifying-glass",
    "user": "user",
    "users": "users",
    "trash": "trash",
    "edit": "pencil-simple",
    "back": "arrow-left",
    "external": "arrow-square-out",
    "close": "x",
    "info": "info",
    "archive": "archive",
    "shield": "shield-check",
    "seal": "seal-check",
    "file": "file-text",
    "chevron": "caret-right",
    "logout": "sign-out",
    "crosshair": "crosshair",
    "clipboard": "clipboard-text",
    "retry": "arrow-clockwise",
    "list": "list-checks",
    "note": "note-pencil",
    # leftover (bakery) keys, harmless extras here
    "bread": "bread",
    "storefront": "storefront",
    "trend-down": "trend-down",
    "trend-up": "trend-up",
    "basket": "basket",
    "scales": "scales",
    "coffee": "coffee",
    "cake": "cake",
    "cookie": "cookie",
    "sunrise": "sun-horizon",
    "moon": "moon",
    "chart": "chart-line-up",
    "receipt": "receipt",
    "minus": "minus",
    "equals": "equals",
    "package": "package",
    "lightning": "lightning",
    "question": "question",
    "eye": "eye",
    "eye-slash": "eye-slash",
    "star": "star",
    "handshake": "handshake",
    "target": "target",
    "flag": "flag",
    "bag": "shopping-bag-open",
    "timer": "timer",
    "repeat": "repeat",
    "download": "download-simple",
    "upload": "upload-simple",
    "link": "link-simple",
    "key": "key",
    "lock": "lock-simple",
    "thermometer": "thermometer-simple",
    "drop": "drop",
    "wind": "wind",
    "gauge": "gauge",
    "toolbox": "toolbox",
    "truck": "truck",
    "buildings": "buildings",
    "money": "money",
    "percent": "percent",
    "sliders": "sliders-horizontal",
    "funnel": "funnel",
    "sort": "arrows-down-up",
    "dots": "dots-three",
    "pause": "pause",
    "play": "play",
    "prohibit": "prohibit",
}

INNER = re.compile(r"<svg[^>]*>(.*)</svg>", re.S)


def main():
    out = []
    missing = []
    for key in sorted(WANTED):
        path = os.path.join(SRC, WANTED[key] + ".svg")
        if not os.path.exists(path):
            missing.append((key, WANTED[key]))
            continue
        raw = open(path, encoding="utf-8").read()
        m = INNER.search(raw)
        if not m:
            missing.append((key, WANTED[key]))
            continue
        body = m.group(1).strip().replace("\n", "")
        # single quotes are safe: phosphor path data never contains them
        assert "'" not in body, key
        out.append("    '%s' => '%s'," % (key, body))

    if missing:
        print("MISSING:", missing, file=sys.stderr)

    header = """<?php

/*
 * Phosphor Icons, regular weight, MIT licensed, (c) 2023 Phosphor Icons.
 * https://github.com/phosphor-icons/core
 *
 * Only the glyphs this interface actually uses are copied here, as raw path
 * data on a 256 unit grid, so that the app needs no icon package, no font and
 * no build step. Regenerate with scripts/build-icons.py after adding a name.
 *
 * Emoji were used here once. They are font dependent, render differently on
 * every phone, and cannot take a colour from the design tokens, so an icon
 * that has to mean "overdue" could not be relied on to look like anything.
 */

return [
"""
    body = "\n".join(out) + "\n];\n"
    dest = sys.argv[1] if len(sys.argv) > 1 else "/home/andis/installbook/resources/icons.php"
    with open(dest, "w", encoding="utf-8") as fh:
        fh.write(header + body)
    print("wrote %d icons to %s" % (len(out), dest))


if __name__ == "__main__":
    main()
