<?php

declare(strict_types=1);

namespace Nexus\OrgStructure\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReportingLine extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $table = 'org_reporting_lines';

    protected $fillable = [
        'tenant_id',
        'manager_employee_id',
        'subordinate_employee_id',
        'position_id',
        'effective_from',
        'effective_to',
        'metadata',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'metadata' => 'array',
    ];
}
