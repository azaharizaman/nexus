<?php

declare(strict_types=1);

namespace Nexus\Hrm\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Nexus\Hrm\Enums\LeaveStatus;

class LeaveRequest extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'hrm_leave_requests';

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'days',
        'status',
        'approval_chain',
        'workflow_instance_id',
        'reason',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'days' => 'decimal:2',
        'status' => LeaveStatus::class,
        'approval_chain' => 'array',
    ];
}
