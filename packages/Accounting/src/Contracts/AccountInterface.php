<?php

declare(strict_types=1);

namespace Nexus\Accounting\Contracts;

interface AccountInterface
{
    public function getId(): int;

    public function getTenantId(): int;

    public function getName(): string;

    public function getCode(): string;

    public function getType(): string;

    public function isActive(): bool;

    public function getParentId(): ?int;

    public function getLeft(): int;

    public function getRight(): int;

    public function getTags(): array;
}
