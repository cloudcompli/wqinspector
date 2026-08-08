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
     * EVERY quote has to be doubled, not just the first. Escaping only the first
     * occurrence passes any suite whose payloads carry a single quote, and still
     * lets a value close its literal.
     */
    public function testTextDoublesEveryQuoteNotJustTheFirst()
    {
        $this->assertSame("'x'''' OR 1=1 --'", SoqlLiteral::text("x'' OR 1=1 --"));
        $this->assertSame("'x'''''' OR 1=1 --'", SoqlLiteral::text("x''' OR 1=1 --"));
        $this->assertSame("''''''''''", SoqlLiteral::text("''''"));
        $this->assertSame("'a''b''c''d'", SoqlLiteral::text("a'b'c'd"));
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
            'object with __toString' => [new SoqlLiteralStringable()],
            'resource' => [fopen('php://memory', 'r')],
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

    /**
     * Callers pass real floats, not their string spellings. On PHP 5.6 a plain
     * (string) cast honours LC_NUMERIC, so under a comma-decimal locale a
     * coordinate renders as "33,68813" — which the grammar check would refuse,
     * turning every within_circle query into an exception.
     */
    public function testNumberAcceptsRealFloatsIndependentlyOfLocale()
    {
        $original = setlocale(LC_NUMERIC, '0');

        $this->assertSame('33.68813', SoqlLiteral::number(33.68813));
        $this->assertSame('-117.819', SoqlLiteral::number(-117.819));
        $this->assertSame('1', SoqlLiteral::number(1.0));
        $this->assertSame('20000', SoqlLiteral::number(20000));

        // Only asserts under a locale the box actually has installed.
        if(setlocale(LC_NUMERIC, 'de_DE.UTF-8', 'de_DE', 'German_Germany.1252') !== false){
            $this->assertSame('33.68813', SoqlLiteral::number(33.68813));
        }

        setlocale(LC_NUMERIC, $original === false ? 'C' : $original);
    }

    /**
     * The float path renders at 14 decimals, so magnitudes below that collapse to
     * zero and very large ones print their full binary expansion. Pinned here as
     * a recorded decision rather than a surprise: neither is injectable, both are
     * lossy, and neither shape occurs in a coordinate or a radius.
     */
    public function testNumberFloatRenderingIsLossyAtTheExtremes()
    {
        $this->assertSame('0', SoqlLiteral::number(1.0e-30));
        $this->assertSame('0', SoqlLiteral::number(1.5e-16));
        $this->assertSame('0', SoqlLiteral::number(0.0));
        $this->assertSame('0', SoqlLiteral::number(-0.0));
        $this->assertSame('1000000000000000019884624838656', SoqlLiteral::number(1.0e30));
    }

    /**
     * @dataProvider nonFiniteFloats
     */
    public function testNumberRejectsNonFiniteFloats($value)
    {
        $this->setExpectedException('InvalidArgumentException');
        SoqlLiteral::number($value);
    }

    public static function nonFiniteFloats()
    {
        return [
            'INF'  => [INF],
            '-INF' => [-INF],
            'NAN'  => [NAN],
        ];
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
            'false'          => [false],
            'array'          => [[1]],
            'object'         => [new stdClass()],
            'resource'       => [fopen('php://memory', 'r')],
        ];
    }
}

class SoqlLiteralStringable
{
    public function __toString()
    {
        return "x' OR 1=1 --";
    }
}
