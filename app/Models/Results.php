<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Results extends Model
{
    protected $table = 'results';
    protected $primaryKey = 'result_id';
    protected $fillable = [
        'school_result_id',
        'results',
    ];
}
