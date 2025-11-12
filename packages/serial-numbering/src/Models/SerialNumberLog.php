<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\SerialNumbering\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Serial Number Log Model
 *
 * Represents an audit log entry for a generated serial number.
 * This is an append-only table for immutable audit trail.
 *
 * @property int $id
 * @property string $tenant_id
 * @property string $sequence_name
 * @property string $generated_number
 * @property string|null $causer_type
 * @property int|null $causer_id
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 */
class SerialNumberLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'serial_number_logs';

    /**
     * Indicates if the model should be timestamped.
     * Only created_at is used (append-only table).
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'tenant_id',
        'sequence_name',
        'generated_number',
        'causer_type',
        'causer_id',
        'metadata',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get the sequence this log belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function sequence(): BelongsTo
    {
        return $this->belongsTo(Sequence::class, 'sequence_name', 'sequence_name')
            ->where('tenant_id', $this->tenant_id);
    }

    /**
     * Get the causer (polymorphic relation).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function causer(): BelongsTo
    {
        return $this->morphTo();
    }
}
