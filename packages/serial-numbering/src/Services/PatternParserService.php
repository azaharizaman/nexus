<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\SerialNumbering\Services;

use Azaharizaman\Erp\SerialNumbering\Contracts\PatternParserContract;
use Azaharizaman\Erp\SerialNumbering\Exceptions\InvalidPatternException;

/**
 * Pattern Parser Service
 *
 * Parses and evaluates serial number patterns with variable substitution.
 *
 * Supported variables:
 * - {YEAR} or {YEAR:2} - 4-digit or 2-digit year
 * - {MONTH} - 2-digit month
 * - {DAY} - 2-digit day
 * - {COUNTER} or {COUNTER:N} - Auto-increment with optional padding
 * - {PREFIX} - Custom prefix
 * - {TENANT} - Tenant code
 * - {DEPARTMENT} - Department code
 */
class PatternParserService implements PatternParserContract
{
    /**
     * Supported variable names
     *
     * @var array<string>
     */
    private const SUPPORTED_VARIABLES = [
        'YEAR',
        'MONTH',
        'DAY',
        'COUNTER',
        'PREFIX',
        'TENANT',
        'DEPARTMENT',
    ];

    /**
     * Pattern regex for variable matching
     *
     * Matches: {VARIABLE} or {VARIABLE:N}
     *
     * @var string
     */
    private const PATTERN_REGEX = '/{([A-Z]+)(?::(\d+))?}/i';

    /**
     * Parse a pattern with given context and return the evaluated string.
     *
     * @param  string  $pattern  The pattern to parse
     * @param  array<string, mixed>  $context  Context values for variable substitution
     * @return string The evaluated pattern
     *
     * @throws InvalidPatternException
     */
    public function parse(string $pattern, array $context): string
    {
        if (! $this->validate($pattern)) {
            throw InvalidPatternException::create($pattern, 'Pattern contains invalid variables');
        }

        return preg_replace_callback(
            self::PATTERN_REGEX,
            function (array $matches) use ($context) {
                $variable = strtoupper($matches[1]);
                $option = $matches[2] ?? null;

                return $this->evaluateVariable($variable, $option, $context);
            },
            $pattern
        ) ?? $pattern;
    }

    /**
     * Validate a pattern syntax.
     *
     * @param  string  $pattern  The pattern to validate
     * @return bool True if valid
     */
    public function validate(string $pattern): bool
    {
        preg_match_all(self::PATTERN_REGEX, $pattern, $matches);

        if (empty($matches[1])) {
            return true; // No variables, valid pattern
        }

        foreach ($matches[1] as $index => $variable) {
            $variable = strtoupper($variable);

            if (! in_array($variable, self::SUPPORTED_VARIABLES, true)) {
                return false;
            }

            // Validate COUNTER padding if specified
            if ($variable === 'COUNTER' && isset($matches[2][$index]) && $matches[2][$index] !== '') {
                $padding = (int) $matches[2][$index];
                if ($padding < 1 || $padding > 10) {
                    return false;
                }
            }

            // Validate YEAR option if specified
            if ($variable === 'YEAR' && isset($matches[2][$index]) && $matches[2][$index] !== '') {
                $option = (int) $matches[2][$index];
                if ($option !== 2) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Extract all variables from a pattern.
     *
     * @param  string  $pattern  The pattern to analyze
     * @return array<string> List of variable names
     */
    public function getVariables(string $pattern): array
    {
        preg_match_all(self::PATTERN_REGEX, $pattern, $matches);

        if (empty($matches[0])) {
            return [];
        }

        $variables = [];
        foreach ($matches[1] as $index => $variable) {
            $fullVariable = strtoupper($variable);
            if (isset($matches[2][$index]) && $matches[2][$index] !== '') {
                $fullVariable .= ':'.$matches[2][$index];
            }
            $variables[] = $fullVariable;
        }

        return array_unique($variables);
    }

    /**
     * Generate a preview of the pattern without consuming the counter.
     *
     * @param  string  $pattern  The pattern to preview
     * @param  array<string, mixed>  $context  Context values
     * @return string The preview string
     */
    public function preview(string $pattern, array $context): string
    {
        // Use sample counter value for preview
        $previewContext = array_merge($context, [
            'counter' => $context['counter'] ?? 1,
        ]);

        return $this->parse($pattern, $previewContext);
    }

    /**
     * Evaluate a single variable with its context.
     *
     * @param  string  $variable  The variable name
     * @param  string|null  $option  Optional parameter
     * @param  array<string, mixed>  $context  Context values
     * @return string The evaluated value
     */
    private function evaluateVariable(string $variable, ?string $option, array $context): string
    {
        return match ($variable) {
            'YEAR' => $this->evaluateYear($option),
            'MONTH' => date('m'),
            'DAY' => date('d'),
            'COUNTER' => $this->evaluateCounter($option, $context),
            'PREFIX' => (string) ($context['prefix'] ?? ''),
            'TENANT' => (string) ($context['tenant_code'] ?? ''),
            'DEPARTMENT' => (string) ($context['department_code'] ?? ''),
            default => '',
        };
    }

    /**
     * Evaluate YEAR variable.
     *
     * @param  string|null  $option  '2' for 2-digit year, null for 4-digit
     * @return string The year string
     */
    private function evaluateYear(?string $option): string
    {
        if ($option === '2') {
            return date('y');
        }

        return date('Y');
    }

    /**
     * Evaluate COUNTER variable.
     *
     * @param  string|null  $option  Padding width
     * @param  array<string, mixed>  $context  Context values
     * @return string The padded counter
     */
    private function evaluateCounter(?string $option, array $context): string
    {
        $counter = (int) ($context['counter'] ?? 0);
        $padding = $option !== null ? (int) $option : (int) ($context['padding'] ?? 5);

        return str_pad((string) $counter, $padding, '0', STR_PAD_LEFT);
    }
}
