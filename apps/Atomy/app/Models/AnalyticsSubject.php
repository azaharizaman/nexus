<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Nexus\Tenancy\Traits\BelongsToTenant;
use Nexus\Atomy\Support\Traits\HasAnalytics;

class AnalyticsSubject extends Model
{
    use BelongsToTenant;
    use HasAnalytics;

    protected $table = 'analytics_subjects';

    protected $fillable = [
        'tenant_id',
        'name'
    ];

    public function analytics(): array
    {
        return [
            'queries' => [
                'simple_list' => [
                    'select' => function ($q) { return $q->get(); },
                    'guards' => [function ($q) { return true; }],
                ],
            ],
        ];
    }
}
