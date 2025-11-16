<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalLine extends Model
{
    public $timestamps = false;

    protected $table = 'journal_lines';

    protected $fillable = [
        'journal_id',
        'account_id',
        'debit',
        'credit',
        'base_amount',
        'foreign_amount',
        'exchange_rate',
        'description'
    ];
}
