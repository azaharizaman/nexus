<?php

declare(strict_types=1);

namespace Nexus\Analytics\Core\Contracts;

use Nexus\Analytics\Core\ValueObjects\PredictionRequest;
use Nexus\Analytics\Core\ValueObjects\PredictionResult;

interface PredictionEngineContract
{
    public function predict(PredictionRequest $request): PredictionResult;

    public function train(string $modelName, array $data): void;
}
