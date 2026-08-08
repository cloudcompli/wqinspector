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
     * start a new clause. Backslash is not an escape character inside a
     * single-quoted SoQL literal, and the lexer matches `''` ahead of the
     * terminator, so a value ending in a backslash cannot consume the closing
     * quote — doubling alone is enough to contain the value.
     *
     * Backslashes do still reach the backend, which reinterprets them within the
     * value (`\n` arrives as a newline, and a trailing one can draw a 500). That
     * is a fidelity problem for the data, not a way out of the literal.
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
     * that is not a plain number is rejected rather than coerced, so a bad
     * caller fails loudly instead of silently querying something else.
     *
     * The digits the caller gave are passed through as written rather than run
     * through PHP's float conversion, which rounds at `precision` and would turn
     * a coordinate like -117.81900000000001 into -117.819, or a large integer
     * into E-notation. The pattern is narrower than is_numeric() on purpose: it
     * admits only what SoQL's own number grammar accepts, so hex, INF/NAN and
     * leading-dot forms are refused instead of reaching the query malformed.
     *
     * @param mixed $value
     * @return string
     */
    public static function number($value)
    {
        if(is_bool($value) || !is_scalar($value)){
            throw new InvalidArgumentException('SoQL numeric literal expects a number, got '.gettype($value));
        }

        $candidate = trim((string)$value);

        if(!preg_match('/^[+-]?[0-9]+(\.[0-9]+)?([eE][+-]?[0-9]+)?$/', $candidate)){
            throw new InvalidArgumentException('SoQL numeric literal expects a number, got '.var_export($value, true));
        }

        // SoQL's number rule takes a lowercase exponent and no leading plus.
        return strtolower(ltrim($candidate, '+'));
    }
}
