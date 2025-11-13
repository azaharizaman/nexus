<?php

declare(strict_types=1);

use Nexus\Erp\SerialNumbering\Exceptions\InvalidPatternException;
use Nexus\Erp\SerialNumbering\Services\PatternParserService;

test('can parse pattern with YEAR variable', function () {
    $parser = new PatternParserService();
    
    $result = $parser->parse('INV-{YEAR}', []);
    
    expect($result)->toContain('INV-'.date('Y'));
});

test('can parse pattern with 2-digit YEAR variable', function () {
    $parser = new PatternParserService();
    
    $result = $parser->parse('INV-{YEAR:2}', []);
    
    expect($result)->toContain('INV-'.date('y'));
});

test('can parse pattern with MONTH variable', function () {
    $parser = new PatternParserService();
    
    $result = $parser->parse('INV-{MONTH}', []);
    
    expect($result)->toBe('INV-'.date('m'));
});

test('can parse pattern with DAY variable', function () {
    $parser = new PatternParserService();
    
    $result = $parser->parse('INV-{DAY}', []);
    
    expect($result)->toBe('INV-'.date('d'));
});

test('can parse pattern with COUNTER variable', function () {
    $parser = new PatternParserService();
    
    $result = $parser->parse('INV-{COUNTER}', ['counter' => 123, 'padding' => 5]);
    
    expect($result)->toBe('INV-00123');
});

test('can parse pattern with COUNTER and custom padding', function () {
    $parser = new PatternParserService();
    
    $result = $parser->parse('INV-{COUNTER:8}', ['counter' => 123]);
    
    expect($result)->toBe('INV-00000123');
});

test('can parse pattern with PREFIX variable', function () {
    $parser = new PatternParserService();
    
    $result = $parser->parse('{PREFIX}-{COUNTER}', ['prefix' => 'CUSTOM', 'counter' => 1, 'padding' => 3]);
    
    expect($result)->toBe('CUSTOM-001');
});

test('can parse pattern with TENANT variable', function () {
    $parser = new PatternParserService();
    
    $result = $parser->parse('{TENANT}-INV-{COUNTER}', ['tenant_code' => 'ACME', 'counter' => 1, 'padding' => 3]);
    
    expect($result)->toBe('ACME-INV-001');
});

test('can parse pattern with DEPARTMENT variable', function () {
    $parser = new PatternParserService();
    
    $result = $parser->parse('INV-{DEPARTMENT}-{COUNTER}', ['department_code' => 'IT', 'counter' => 1, 'padding' => 3]);
    
    expect($result)->toBe('INV-IT-001');
});

test('can parse complex pattern with multiple variables', function () {
    $parser = new PatternParserService();
    
    $result = $parser->parse(
        '{TENANT}-INV-{YEAR:2}{MONTH}-{COUNTER:5}',
        ['tenant_code' => 'ACME', 'counter' => 42]
    );
    
    expect($result)->toBe('ACME-INV-'.date('ym').'-00042');
});

test('validates correct pattern', function () {
    $parser = new PatternParserService();
    
    expect($parser->validate('INV-{YEAR}-{COUNTER:5}'))->toBeTrue();
});

test('validates pattern without variables', function () {
    $parser = new PatternParserService();
    
    expect($parser->validate('STATIC-NUMBER'))->toBeTrue();
});

test('rejects pattern with invalid variable', function () {
    $parser = new PatternParserService();
    
    expect($parser->validate('INV-{INVALID}'))->toBeFalse();
});

test('rejects pattern with invalid COUNTER padding', function () {
    $parser = new PatternParserService();
    
    expect($parser->validate('INV-{COUNTER:0}'))->toBeFalse();
    expect($parser->validate('INV-{COUNTER:11}'))->toBeFalse();
});

test('can extract variables from pattern', function () {
    $parser = new PatternParserService();
    
    $variables = $parser->getVariables('INV-{YEAR}-{MONTH}-{COUNTER:5}');
    
    expect($variables)->toBe(['YEAR', 'MONTH', 'COUNTER:5']);
});

test('returns empty array when no variables in pattern', function () {
    $parser = new PatternParserService();
    
    $variables = $parser->getVariables('STATIC-NUMBER');
    
    expect($variables)->toBe([]);
});

test('can generate preview without consuming counter', function () {
    $parser = new PatternParserService();
    
    $preview = $parser->preview('INV-{COUNTER:5}', ['counter' => 1]);
    
    expect($preview)->toBe('INV-00001');
});
