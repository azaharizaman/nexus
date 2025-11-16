<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsHistory extends Model
{
    protected $fillable = [
        'subject_type',
        'subject_id',
        'query_name',
        'result',
        'actor_id',
        'tenant_id'
    ];

    protected $casts = [
        'result' => 'array',
        'tenant_id' => 'integer'
    ];
}
