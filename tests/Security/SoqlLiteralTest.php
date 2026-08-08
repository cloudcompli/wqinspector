<?php

use CloudCompli\WQInvestigator\Support\Soql\SoqlLiteral;

/**
 * Contract for the two primitives every SoQL clause is built from.
 *
 * compileWhere() is covered end-to-end in SoqlInjectionTest; this pins the
 * rejection branches and the numeric rendering directly, since a caller can
 * reach these methods without going through a dataset class.
 */
class SoqlLiteralTest extends PHPUnit_Framework_TestCase
{
    public function testTextQuotesAndDoublesEmbeddedQuotes()
    {
        $this->assertSame("'Bob''s'", SoqlLiteral::text("Bob's"));
        $this->assertSame("''''", SoqlLiteral::text("'"));
        $this->assertSame("''", SoqlLiteral::text(''));
        $this->assertSame("'Effluent'", SoqlLiteral::text('Effluent'));
    }

    /**
     * Backslash is not an escape inside a single-quoted SoQL literal, so a value
     * ending in one must not be able to consume the closing quote.
     */
    public function testTextLeavesBackslashesAsData()
    {
        $this->assertSame("'x\\'", SoqlLiteral::text('x\\'));
        $this->assertSame("'x\\'' OR 1=1 --'", SoqlLiteral::text("x\\' OR 1=1 --"));
    }

    public function testTextAcceptsNumbersAsText()
    {
        $this->assertSame("'42'", SoqlLiteral::text(42));
        $this->assertSame("'4.5'", SoqlLiteral::text(4.5));
    }

    /**
     * @dataProvider nonScalars
     */
    public function testTextRejectsAnythingThatIsNotAScalar($value)
    {
        $this->setExpectedException('InvalidArgumentException');
        SoqlLiteral::text($value);
    }

    public static function nonScalars()
    {
        return [
            'null' => [null],
            'true' => [true],
            'false' => [false],
            'array' => [['a']],
            'object' => [new stdClass()],
        ];
    }

    /**
     * The caller's digits are passed through as written — running them through
     * PHP's float conversion rounds at `precision` and loses coordinate detail.
     */
    public function testNumberPreservesTheCallersDigits()
    {
        $this->assertSame('-117.81900000000001', SoqlLiteral::number('-117.81900000000001'));
        $this->assertSame('9223372036854775808', SoqlLiteral::number('9223372036854775808'));
        $this->assertSame('33.68813', SoqlLiteral::number('33.68813'));
        $this->assertSame('20000', SoqlLiteral::number('20000'));
        $this->assertSame('20000', SoqlLiteral::number(20000));
    }

    public function testNumberNormalisesToSoqlsNumberGrammar()
    {
        $this->assertSame('1e5', SoqlLiteral::number('1E5'));
        $this->assertSame('5', SoqlLiteral::number('+5'));
        $this->assertSame('-5', SoqlLiteral::number('-5'));
        $this->assertSame('12', SoqlLiteral::number('  12  '));
    }

    /**
     * @dataProvider notNumbers
     */
    public function testNumberRejectsAnythingThatIsNotAPlainNumber($value)
    {
        $this->setExpectedException('InvalidArgumentException');
        SoqlLiteral::number($value);
    }

    public static function notNumbers()
    {
        return [
            'breakout'       => ['0) OR 1=1 OR within_circle(location, 0, 0, 1'],
            'trailing text'  => ['12abc'],
            'hex'            => ['0x1A'],
            'leading dot'    => ['.5'],
            'INF'            => ['INF'],
            'NAN'            => ['NAN'],
            'empty'          => [''],
            'null'           => [null],
            'true'           => [true],
            'array'          => [[1]],
            'object'         => [new stdClass()],
        ];
    }
}
