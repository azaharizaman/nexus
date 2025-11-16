<?php

declare(strict_types=1);

namespace Nexus\Analytics\Core\ValueObjects;

final class PredictionResult
{
    public function __construct(public readonly array $predictions = [], public readonly float $confidence = 0.0) {}
}
