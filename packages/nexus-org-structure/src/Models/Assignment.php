<?php

declare(strict_types=1);

namespace Nexus\OrgStructure\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assignment extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $table = 'org_assignments';

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'position_id',
        'org_unit_id',
        'effective_from',
        'effective_to',
        'is_primary',
        'metadata',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_primary' => 'boolean',
        'metadata' => 'array',
    ];
}
