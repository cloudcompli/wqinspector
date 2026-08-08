<?php

namespace CloudCompli\WQInvestigator\Support\Soql;

use InvalidArgumentException;

/**
 * Builds SoQL literals that cannot break out of the expression they sit in.
 *
 * Socrata's SODA API takes `$where` as a string; there is no bind-parameter
 * facility, so every value a caller supplies has to be made safe before it is
 * concatenated. These two methods are the only sanctioned way to put a caller
 * value into a SoQL clause.
 */
class SoqlLiteral
{
    /**
     * Quote a value as a SoQL string literal.
     *
     * SoQL delimits strings with single quotes and escapes an embedded quote by
     * doubling it, so a value carrying `'` can no longer close the literal and
     * start a new clause.
     *
     * @param mixed $value
     * @return string the value including its surrounding quotes
     */
    public static function text($value)
    {
        if(is_bool($value) || is_null($value) || is_array($value) || is_object($value)){
            throw new InvalidArgumentException('SoQL string literal expects a scalar value, got '.gettype($value));
        }

        return "'".str_replace("'", "''", (string)$value)."'";
    }

    /**
     * Render a value as a SoQL numeric literal.
     *
     * Numbers sit unquoted in SoQL, so there is nothing to escape — an
     * unexpected value here would land in the clause as raw syntax. Anything
     * non-numeric is rejected rather than coerced, so a bad caller fails loudly
     * instead of silently querying something else.
     *
     * @param mixed $value
     * @return string
     */
    public static function number($value)
    {
        if(is_bool($value) || !is_scalar($value) || !is_numeric($value)){
            $shown = is_scalar($value) ? var_export($value, true) : gettype($value);
            throw new InvalidArgumentException('SoQL numeric literal expects a number, got '.$shown);
        }

        return (string)($value + 0);
    }
}
