<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Nexus\Tenancy\Traits\BelongsToTenant;

class AnalyticsDefinition extends Model
{
    use BelongsToTenant;

    protected $table = 'analytics_definitions';

    protected $fillable = [
        'name',
        'label',
        'schema',
        'metrics',
        'tenant_id',
        'active'
    ];

    protected $casts = [
        'schema' => 'array',
        'metrics' => 'array',
        'active' => 'boolean'
    ];
}
