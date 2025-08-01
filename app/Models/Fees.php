<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fees extends Model
{
    protected $table = 'fees';
    protected $primaryKey = 'fee_id';
    protected $fillable = [
        'school_result_id',
        'fee_type',
        'fee_amount',
    ];
    public $timestamps = false;
}
