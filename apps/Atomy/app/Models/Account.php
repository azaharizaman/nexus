<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Nexus\Tenancy\Traits\BelongsToTenant;
use Nexus\Accounting\Contracts\AccountInterface;

class Account extends Model implements AccountInterface
{
    use BelongsToTenant;
    protected $table = 'accounts';

    protected $casts = [
        'tags' => 'array',
        'is_active' => 'boolean',
    ];

    protected $fillable = [
        'tenant_id',
        'parent_id',
        'code',
        'name',
        'type',
        'is_active',
        'tags',
        'reporting_group',
        'lft',
        'rgt'
    ];

    public function getId(): int
    {
        return intval($this->getKey());
    }

    public function getTenantId(): int
    {
        return intval($this->tenant_id);
    }

    public function getName(): string
    {
        return (string)$this->name;
    }

    public function getCode(): string
    {
        return (string)$this->code;
    }

    public function getType(): string
    {
        return (string)$this->type;
    }

    public function isActive(): bool
    {
        return (bool)$this->is_active;
    }

    public function getParentId(): ?int
    {
        return $this->parent_id ? intval($this->parent_id) : null;
    }

    public function getLeft(): int
    {
        return intval($this->lft);
    }

    public function getRight(): int
    {
        return intval($this->rgt);
    }

    public function getTags(): array
    {
        return (array)$this->tags;
    }
}
