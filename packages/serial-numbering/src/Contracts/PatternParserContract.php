<?php

declare(strict_types=1);

namespace Nexus\Erp\SerialNumbering\Contracts;

/**
 * Pattern Parser Contract
 *
 * Defines the interface for parsing and evaluating serial number patterns.
 */
interface PatternParserContract
{
    /**
     * Parse a pattern with given context and return the evaluated string.
     *
     * Variables in the pattern (e.g., {YEAR}, {COUNTER}) are replaced
     * with their evaluated values from the context.
     *
     * @param  string  $pattern  The pattern to parse (e.g., "INV-{YEAR}-{COUNTER:5}")
     * @param  array<string, mixed>  $context  Context values for variable substitution
     * @return string The evaluated pattern
     *
     * @throws \Nexus\Erp\SerialNumbering\Exceptions\InvalidPatternException
     */
    public function parse(string $pattern, array $context): string;

    /**
     * Validate a pattern syntax.
     *
     * Checks if the pattern contains only recognized variables
     * and has valid syntax.
     *
     * @param  string  $pattern  The pattern to validate
     * @return bool True if valid, false otherwise
     */
    public function validate(string $pattern): bool;

    /**
     * Extract all variables from a pattern.
     *
     * Returns an array of variable names found in the pattern.
     * Example: "INV-{YEAR}-{COUNTER:5}" returns ['YEAR', 'COUNTER:5']
     *
     * @param  string  $pattern  The pattern to analyze
     * @return array<string> List of variable names
     */
    public function getVariables(string $pattern): array;

    /**
     * Generate a preview of the pattern without consuming the counter.
     *
     * Uses sample data to show what the generated number would look like.
     *
     * @param  string  $pattern  The pattern to preview
     * @param  array<string, mixed>  $context  Context values (counter is replaced with sample)
     * @return string The preview string
     */
    public function preview(string $pattern, array $context): string;
}
