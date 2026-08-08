<?php

use CloudCompli\WQInvestigator\CIWQS\ESMR;
use CloudCompli\WQInvestigator\SMARTS\StormwaterViolations;

/**
 * Regression for 0.1u — SOQL injection in the two compileWhere() implementations.
 *
 * Every option value below used to be concatenated into the `$where` clause with
 * no escaping, so a value carrying a single quote closed the literal it sat in
 * and the remainder parsed as SoQL. Each string case fails against the pre-fix code.
 *
 * The cases are table-driven across every option and every array index, because
 * pinning one payload position leaves the others free to regress: escaping only
 * within_circle[2], or only the first violation_type, or only `after`, all still
 * yield a working injection.
 */
class SoqlInjectionTest extends PHPUnit_Framework_TestCase
{
    /** Closes the literal, appends an always-true predicate. */
    const BREAKOUT = "x' OR 1=1 --";

    /**
     * Every option key each class reads, and how to build options that put a
     * payload at one specific position. assertEveryOptionIsCovered() checks this
     * list against the source, so a new option cannot be added without a case.
     */
    public static function stringPositions()
    {
        return [
            'ESMR after'  => ['CloudCompli\WQInvestigator\CIWQS\ESMR', ['after' => self::BREAKOUT, 'before' => '2016-01-01']],
            'ESMR before' => ['CloudCompli\WQInvestigator\CIWQS\ESMR', ['after' => '2015-01-01', 'before' => self::BREAKOUT]],
            'SV after'    => ['CloudCompli\WQInvestigator\SMARTS\StormwaterViolations', ['after' => self::BREAKOUT, 'before' => '2016-01-01']],
            'SV before'   => ['CloudCompli\WQInvestigator\SMARTS\StormwaterViolations', ['after' => '2015-01-01', 'before' => self::BREAKOUT]],
            'SV violation_type first'  => ['CloudCompli\WQInvestigator\SMARTS\StormwaterViolations', ['violation_type' => [self::BREAKOUT, 'Effluent']]],
            'SV violation_type second' => ['CloudCompli\WQInvestigator\SMARTS\StormwaterViolations', ['violation_type' => ['Effluent', self::BREAKOUT]]],
        ];
    }

    public static function numericPositions()
    {
        $classes = ['CloudCompli\WQInvestigator\CIWQS\ESMR', 'CloudCompli\WQInvestigator\SMARTS\StormwaterViolations'];
        $cases = [];
        foreach($classes as $class){
            for($index = 0; $index < 3; $index++){
                $circle = ['0', '0', '0'];
                $circle[$index] = '0) OR 1=1 OR within_circle(location, 0, 0, 1';
                $cases[$class.' within_circle['.$index.']'] = [$class, ['within_circle' => $circle]];
            }
        }
        return $cases;
    }

    /* ---------------------------------------------------------------- */

    /**
     * Guards against the suite passing while pointed at an installed copy of the
     * library rather than this working tree, which is what happens when a
     * consuming application's autoloader resolves the namespace first.
     */
    public function testTheClassesUnderTestAreLoadedFromThisWorkingTree()
    {
        $expected = realpath(dirname(dirname(__DIR__)).'/src');

        foreach(['CloudCompli\WQInvestigator\CIWQS\ESMR',
                 'CloudCompli\WQInvestigator\SMARTS\StormwaterViolations',
                 'CloudCompli\WQInvestigator\Support\Soql\SoqlLiteral'] as $name){
            $class = new ReflectionClass($name);
            $this->assertStringStartsWith(
                $expected,
                realpath($class->getFileName()),
                $name.' was loaded from outside this repository, so the suite is not testing this branch'
            );
        }
    }

    /**
     * The payload's characters survive — they are data. What must not survive is
     * a quote that closes the literal, letting the rest parse as SoQL.
     */
    public function testNoStringOptionCanEscapeItsLiteral()
    {
        foreach(self::stringPositions() as $label => $case){
            list($class, $options) = $case;

            $dataset = new $class();
            $dataset->setOptions($options);

            $this->assertPayloadStaysInsideALiteral($dataset->compileWhere(), 'OR 1=1 --', $label);
        }
    }

    /**
     * within_circle() takes bare numbers, so there is no literal to escape — an
     * unexpected value lands in the clause as raw syntax and must be refused.
     */
    public function testNoNumericOptionAcceptsNonNumericInput()
    {
        foreach(self::numericPositions() as $label => $case){
            list($class, $options) = $case;

            $dataset = new $class();
            $dataset->setOptions($options);

            try {
                $clause = $dataset->compileWhere();
                $this->fail($label.' accepted non-numeric input and compiled: '.$clause);
            } catch (InvalidArgumentException $e) {
                $this->assertTrue(true);
            }
        }
    }

    /**
     * The list above is only as good as its coverage, so check it against the
     * source: any option compileWhere() reads must have a case here. Adding an
     * option and interpolating it raw fails this test.
     */
    public function testEveryOptionReadByCompileWhereIsCovered()
    {
        $covered = [
            'CloudCompli\WQInvestigator\CIWQS\ESMR' => ['after', 'before', 'within_circle'],
            'CloudCompli\WQInvestigator\SMARTS\StormwaterViolations' => ['after', 'before', 'within_circle', 'violation_type'],
        ];

        foreach($covered as $class => $expected){
            $method = new ReflectionMethod($class, 'compileWhere');
            $source = implode('', array_slice(
                file($method->getFileName()),
                $method->getStartLine() - 1,
                $method->getEndLine() - $method->getStartLine() + 1
            ));

            preg_match_all("/array_key_exists\('([a-z_]+)', \\\$this->_options\)/", $source, $matches);
            $read = array_values(array_unique($matches[1]));

            sort($read);
            sort($expected);
            $this->assertSame(
                $expected,
                $read,
                $class.'::compileWhere() reads an option with no injection case in this test — add one to stringPositions() or numericPositions()'
            );
        }
    }

    /* ---- characterization: the ordinary path still produces the same clause ---- */

    public function testEsmrCompilesTheExpectedClauseForOrdinaryOptions()
    {
        $esmr = new ESMR();
        $esmr->setOptions([
            'after' => '2015-01-01T00:00:00',
            'before' => '2016-01-01T00:00:00',
            'within_circle' => ['33.68813', '-117.819', '20000'],
        ]);

        $this->assertSame(
            "(sample_date between '2015-01-01T00:00:00' and '2016-01-01T00:00:00')"
                ." AND (within_circle(location, 33.68813, -117.819, 20000))",
            $esmr->compileWhere()
        );
    }

    public function testViolationsCompilesTheExpectedClauseForOrdinaryOptions()
    {
        $violations = new StormwaterViolations();
        $violations->setOptions([
            'after' => '2015-01-01T00:00:00',
            'before' => '2016-01-01T00:00:00',
            'within_circle' => ['33.68813', '-117.819', '20000'],
            'violation_type' => ['Effluent', 'Unregulated Discharge'],
        ]);

        $this->assertSame(
            "(occurred_on > '2015-01-01T00:00:00' and occurred_on < '2016-01-01T00:00:00')"
                ." AND (within_circle(location_1, 33.68813, -117.819, 20000))"
                ." AND (violation_type = 'Effluent' OR violation_type = 'Unregulated Discharge')",
            $violations->compileWhere()
        );
    }

    public function testCompileWhereIsNullWhenNoFilteringOptionsAreSet()
    {
        $esmr = new ESMR();

        $this->assertNull($esmr->compileWhere());
    }

    /**
     * makeQueryParameters read $params['where'] while checking $params['$where'],
     * so a caller-supplied clause was dropped and an undefined-index notice raised.
     */
    public function testMakeQueryParametersCombinesACallerSuppliedWhereClause()
    {
        $esmr = new ESMR();
        $esmr->setOptions([
            'after' => '2015-01-01T00:00:00',
            'before' => '2016-01-01T00:00:00',
        ]);

        $params = $esmr->makeQueryParameters(['$where' => "parameter = 'Selenium, Total'"]);

        $this->assertSame(
            "(parameter = 'Selenium, Total')"
                ." AND ((sample_date between '2015-01-01T00:00:00' and '2016-01-01T00:00:00'))",
            $params['$where']
        );
    }

    /* ---------------------------------------------------------------- */

    /**
     * Walks the clause the way SoQL's lexer does — a doubled quote is an escaped
     * quote, a lone quote toggles the literal — and asserts the payload never
     * appears outside a literal. This is the property that matters: not that the
     * payload is absent, but that it never becomes syntax.
     */
    protected function assertPayloadStaysInsideALiteral($clause, $needle, $label)
    {
        $this->assertNotNull($clause, $label.' compiled to nothing');

        $inLiteral = false;
        $outsideText = '';

        for($i = 0; $i < strlen($clause); $i++){
            if($clause[$i] === "'"){
                if($inLiteral && isset($clause[$i + 1]) && $clause[$i + 1] === "'"){
                    $i++;      // an escaped quote — still inside the literal
                    continue;
                }
                $inLiteral = !$inLiteral;
                continue;
            }
            if(!$inLiteral){
                $outsideText .= $clause[$i];
            }
        }

        $this->assertFalse($inLiteral, $label.' left an unterminated string literal: '.$clause);
        $this->assertNotContains(
            $needle,
            $outsideText,
            $label.' let the payload out of its literal and into SoQL syntax: '.$clause
        );
    }
}
