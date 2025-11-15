<?php

declare(strict_types=1);

namespace Nexus\OrgStructure\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Position extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $table = 'org_positions';

    protected $fillable = [
        'tenant_id',
        'title',
        'code',
        'org_unit_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];
}
