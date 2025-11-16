<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Nexus\Tenancy\Traits\BelongsToTenant;
use Nexus\Accounting\Contracts\JournalEntryInterface;

class JournalEntry extends Model implements JournalEntryInterface
{
    use BelongsToTenant;
    protected $table = 'journal_entries';

    protected $casts = [
        'is_posted' => 'boolean',
        'posted_at' => 'datetime',
    ];

    protected $fillable = ['tenant_id', 'reference', 'description', 'created_by', 'is_posted', 'posted_at'];

    public function getId(): int
    {
        return intval($this->getKey());
    }

    public function getTenantId(): int
    {
        return intval($this->tenant_id);
    }

    public function getPostedAt(): ?\DateTimeImmutable
    {
        return $this->posted_at ? \DateTimeImmutable::createFromMutable($this->posted_at->toDateTime()) : null;
    }

    public function getDescription(): string
    {
        return (string)$this->description;
    }

    public function isPosted(): bool
    {
        return (bool)$this->is_posted;
    }
}
