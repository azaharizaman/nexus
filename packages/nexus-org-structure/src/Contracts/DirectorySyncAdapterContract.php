<?php

declare(strict_types=1);

namespace Nexus\OrgStructure\Contracts;

interface DirectorySyncAdapterContract
{
    /** Configure adapter with tenant-scoped settings (host, base DN, creds, etc.). */
    public function configure(array $settings): void;

    /** Quick connectivity check without side effects. */
    public function testConnection(): bool;

    /**
     * Fetch external changes since a cursor/token.
     * Returns iterable of normalized records (org units, positions, assignments).
     */
    public function fetchChanges(?string $sinceCursor = null): iterable;

    /** Adapter-specific opaque cursor to resume incremental syncs. */
    public function currentCursor(): ?string;
}
