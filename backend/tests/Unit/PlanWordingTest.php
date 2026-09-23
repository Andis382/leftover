<?php

namespace Tests\Unit;

use App\Forecast\Headline;
use App\Forecast\Reason;
use App\Forecast\ReasonText;
use Tests\TestCase;

/** The plan's sentences, in both languages the bakery may read them in. */
class PlanWordingTest extends TestCase
{
    public function test_the_headline_names_the_biggest_changes_then_the_rest_as_usual(): void
    {
        $headline = Headline::from([
            ['name' => 'Kroasan i thjeshtë', 'change' => -5],
            ['name' => 'Simite me susam', 'change' => 10],
            ['name' => 'Bukë misri', 'change' => 0],
        ]);

        $this->assertSame('10 more simite me susam, 5 fewer kroasan i thjeshtë, the rest as usual.', $headline->render('en', 'last Monday'));
        $this->assertSame('10 më shumë simite me susam, 5 më pak kroasan i thjeshtë, të tjerat si zakonisht.', $headline->render('sq', 'të hënën e kaluar'));
    }

    public function test_the_headline_counts_the_changes_it_does_not_name(): void
    {
        $rows = array_map(fn (int $i) => ['name' => "Produkt $i", 'change' => $i], range(1, 6));

        $this->assertSame('6 more produkt 6, 5 more produkt 5, 4 more produkt 4 and 3 smaller changes.', Headline::from($rows)->render('en', 'last Monday'));
    }

    public function test_the_headline_for_an_unchanged_plan_and_a_first_plan(): void
    {
        $same = Headline::from([['name' => 'Petulla', 'change' => 0]]);
        $first = Headline::from([['name' => 'Petulla', 'change' => null]]);

        $this->assertSame('Gjithçka njësoj si të shtunën e kaluar.', $same->render('sq', ReasonText::weekday('last', 6, 'sq')));
        $this->assertStringStartsWith('Your usual numbers', $first->render('en', 'last Monday'));
    }

    public function test_reasons_read_plainly_in_both_languages(): void
    {
        $leftEach = new Reason(Reason::LEFT_EACH, ['left' => 7]);
        $soldOut = new Reason(Reason::SOLD_OUT, ['time' => '10:20', 'weeksAgo' => 1, 'date' => '2026-09-19']);

        $this->assertSame('7 left over on each of the last two Mondays.', ReasonText::render($leftEach, 1, 'en'));
        $this->assertSame('Mbetën nga 7 copë në secilën nga dy të hënat e fundit.', ReasonText::render($leftEach, 1, 'sq'));
        $this->assertSame('Sold out by 10:20 last Saturday.', ReasonText::render($soldOut, 6, 'en'));
        $this->assertSame('Mbaroi në 10:20 të shtunën e kaluar.', ReasonText::render($soldOut, 6, 'sq'));
    }

    public function test_late_sell_outs_are_phrased_by_the_hour_before_closing(): void
    {
        $late = new Reason(Reason::SOLD_OUT_LATE, ['times' => 3, 'of' => 4]);

        $this->assertSame('Sold out in the last hour on 3 of the last 4 Wednesdays.', ReasonText::render($late, 3, 'en'));
        $this->assertSame('Mbaroi në orën e fundit para mbylljes në 3 nga 4 të mërkurat e fundit.', ReasonText::render($late, 3, 'sq'));
    }

    public function test_an_older_day_is_named_by_its_date(): void
    {
        $reason = new Reason(Reason::LEFT_LAST, ['left' => 9, 'weeksAgo' => 2, 'date' => '2026-09-07']);

        $this->assertSame('9 left over on 7 Sep.', ReasonText::render($reason, 1, 'en'));
        $this->assertSame('Mbetën 9 copë më 7 shtator.', ReasonText::render($reason, 1, 'sq'));
    }

    public function test_a_blended_reason_uses_the_count(): void
    {
        $this->assertSame('Only one counted Friday so far, mixed with your usual number.', ReasonText::render(new Reason(Reason::BLENDED, ['count' => 1]), 5, 'en'));
        $this->assertSame('Only 2 counted Fridays so far, mixed with your usual number.', ReasonText::render(new Reason(Reason::BLENDED, ['count' => 2]), 5, 'en'));
    }
}
