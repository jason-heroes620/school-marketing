<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Facilities extends Model
{
    protected $table = 'facilities';
    protected $primaryKey = 'facility_id';
    protected $fillable = [
        'school_result_id',
        'facility'
    ];
    public $timestamps = false;
}
