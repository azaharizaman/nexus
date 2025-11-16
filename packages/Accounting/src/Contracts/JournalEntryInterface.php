<?php

declare(strict_types=1);

namespace Nexus\Accounting\Contracts;

interface JournalEntryInterface
{
    public function getId(): int;

    public function getTenantId(): int;

    public function getPostedAt(): ?\DateTimeImmutable;

    public function getDescription(): string;

    public function isPosted(): bool;
}
