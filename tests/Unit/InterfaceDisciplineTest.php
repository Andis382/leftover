<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * The rules the interface is supposed to keep, checked rather than remembered.
 *
 * Every one of these was broken by the first version of this app, and none of
 * them is the sort of thing anyone notices while reviewing a template. They are
 * cheap to check and expensive to find by hand on a phone in a boiler room.
 */
class InterfaceDisciplineTest extends TestCase
{
    /** @return array<string, string> path => contents */
    private function views(): array
    {
        $root = dirname(__DIR__, 2).'/resources/views';
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));
        $out = [];

        foreach ($files as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $out[substr($file->getPathname(), strlen($root) + 1)] = file_get_contents($file->getPathname());
            }
        }

        self::assertNotEmpty($out, 'No Blade views found to check.');

        return $out;
    }

    private function stylesheet(): string
    {
        return file_get_contents(dirname(__DIR__, 2).'/public/css/app.css');
    }

    /**
     * Emoji are drawn by whatever font the device happens to carry. They render
     * at a size nobody chose, differ between Android and iOS, cannot take a
     * colour from a token, and several of them are simply missing on older
     * phones. An icon that has to mean "gas" or "overdue" cannot be one.
     */
    public function test_no_emoji_are_used_as_icons(): void
    {
        // Pictographic ranges only. Accented Latin, Albanian ë and ç, and the
        // en dash all have to keep working.
        $emoji = '/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}\x{2B00}-\x{2BFF}\x{FE0F}\x{1F000}-\x{1F2FF}]/u';

        foreach ($this->views() as $path => $body) {
            $this->assertSame(
                0,
                preg_match($emoji, $body),
                "Emoji used as an icon in resources/views/{$path}. Use <x-icon name=\"…\" /> instead."
            );
        }

        $this->assertSame(0, preg_match($emoji, (string) file_get_contents(
            dirname(__DIR__, 2).'/app/Models/Product.php'
        )), 'Product::icon() must return an icon name, not a character.');
    }

    /**
     * Colour is the third signal on a status, after the shape and the word.
     * Every tag therefore carries an icon, so it still means something printed
     * in black and white or read by someone who cannot separate the red from
     * the green.
     */
    public function test_every_status_tag_carries_an_icon_and_a_word(): void
    {
        foreach ($this->views() as $path => $body) {
            // Match the whole element rather than just its attributes: an
            // attribute value may contain a PHP match expression, and "->"
            // would end a naive [^>]* scan in the middle of one.
            preg_match_all('/<x-tag\b.*?<\/x-tag>/s', $body, $matches);

            foreach ($matches[0] as $tag) {
                $this->assertMatchesRegularExpression(
                    '/:?icon=/',
                    $tag,
                    "A status tag without an icon in resources/views/{$path}: ".substr($tag, 0, 90)
                );
            }
        }
    }

    /**
     * A tap target smaller than a fingertip is a target that gets missed while
     * standing on a ladder. 48px is the larger of the two platform minimums.
     */
    public function test_the_tap_target_floor_is_at_least_48px(): void
    {
        preg_match('/--tap:\s*(\d+)px/', $this->stylesheet(), $m);

        $this->assertNotEmpty($m, 'The stylesheet must define a --tap token.');
        $this->assertGreaterThanOrEqual(48, (int) $m[1]);
    }

    /** Anyone who has asked their system for less motion must actually get it. */
    public function test_reduced_motion_is_honoured(): void
    {
        $this->assertStringContainsString(
            'prefers-reduced-motion: reduce',
            $this->stylesheet(),
            'The stylesheet must answer prefers-reduced-motion.'
        );
    }

    /** Focus rings are load-bearing for anyone using a keyboard. */
    public function test_focus_is_never_removed(): void
    {
        $css = $this->stylesheet();

        $this->assertStringContainsString(':focus-visible', $css);
        $this->assertDoesNotMatchRegularExpression(
            '/outline\s*:\s*(none|0)\s*;/i',
            $css,
            'Something in the stylesheet removes a focus outline.'
        );
    }

    /**
     * Colours belong to the token table. A hex code typed into a screen is how
     * a palette stops being a palette and how dark mode quietly breaks.
     */
    public function test_no_screen_invents_its_own_colour(): void
    {
        foreach ($this->views() as $path => $body) {
            // The favicon and the WhatsApp-green mark live in partials/head and
            // are part of the brand, not of a screen.
            if (str_starts_with($path, 'partials'.DIRECTORY_SEPARATOR.'head')) {
                continue;
            }

            $this->assertSame(
                0,
                preg_match('/style="[^"]*#[0-9a-f]{3,8}/i', $body),
                "A raw colour is written into resources/views/{$path}. Use a token from app.css."
            );
        }
    }
}
