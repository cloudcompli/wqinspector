<?php

use CloudCompli\WQInvestigator\CIWQS\ESMR;
use CloudCompli\WQInvestigator\SMARTS\StormwaterViolations;

/**
 * Regression for 0.1u — SOQL injection in the two compileWhere() implementations.
 *
 * Every option value below used to be concatenated into the `$where` clause with
 * no escaping, so a value carrying a single quote closed the literal and the rest
 * of it was parsed as SoQL. Each assertion here fails against the pre-fix code.
 */
class SoqlInjectionTest extends PHPUnit_Framework_TestCase
{
    /** The classic breakout: close the literal, append an always-true predicate. */
    const BREAKOUT = "2015-01-01' or 1=1 --";

    /**
     * Guards against the whole suite passing while pointed at an installed copy
     * of the library rather than this working tree — which is what happens if a
     * consuming application's autoloader resolves the namespace first.
     */
    public function testTheClassesUnderTestAreLoadedFromThisWorkingTree()
    {
        $expected = realpath(dirname(dirname(__DIR__)).'/src');

        foreach([new ReflectionClass('CloudCompli\WQInvestigator\CIWQS\ESMR'),
                 new ReflectionClass('CloudCompli\WQInvestigator\SMARTS\StormwaterViolations')] as $class){
            $this->assertStringStartsWith(
                $expected,
                realpath($class->getFileName()),
                $class->getName().' was loaded from outside this repository, so the suite is not testing this branch'
            );
        }
    }

    public function testEsmrDateOptionCannotCloseTheStringLiteral()
    {
        $esmr = new ESMR();
        $esmr->setOptions([
            'after' => self::BREAKOUT,
            'before' => '2016-01-01T00:00:00',
        ]);

        // The payload's characters survive — they are data. What must not survive is
        // the single quote that closes the literal, so it is doubled.
        $this->assertSame(
            "(sample_date between '2015-01-01'' or 1=1 --' and '2016-01-01T00:00:00')",
            $esmr->compileWhere()
        );
    }

    public function testViolationsDateOptionCannotCloseTheStringLiteral()
    {
        $violations = new StormwaterViolations();
        $violations->setOptions([
            'after' => self::BREAKOUT,
            'before' => '2016-01-01T00:00:00',
        ]);

        $this->assertSame(
            "(occurred_on > '2015-01-01'' or 1=1 --' and occurred_on < '2016-01-01T00:00:00')",
            $violations->compileWhere()
        );
    }

    public function testViolationTypeCannotCloseTheStringLiteral()
    {
        $violations = new StormwaterViolations();
        $violations->setOptions([
            'violation_type' => ["Effluent' OR violation_type LIKE '%"],
        ]);

        $this->assertSame(
            "(violation_type = 'Effluent'' OR violation_type LIKE ''%')",
            $violations->compileWhere()
        );
    }

    /**
     * within_circle takes bare numbers, so there is no literal to escape — an
     * unexpected value would land in the clause as raw syntax. It has to be
     * rejected outright.
     */
    public function testEsmrWithinCircleRejectsNonNumericInput()
    {
        $esmr = new ESMR();
        $esmr->setOptions([
            'within_circle' => ['33.68813', '-117.819', '20000) OR within_circle(location, 0, 0, 99999999'],
        ]);

        $this->setExpectedException('InvalidArgumentException');
        $esmr->compileWhere();
    }

    public function testViolationsWithinCircleRejectsNonNumericInput()
    {
        $violations = new StormwaterViolations();
        $violations->setOptions([
            'within_circle' => ['0', '0', '1 OR 1=1'],
        ]);

        $this->setExpectedException('InvalidArgumentException');
        $violations->compileWhere();
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
}
