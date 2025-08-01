<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResultsFromWeb extends Model
{
    protected $table = 'results_from_web';
    protected $primaryKey = 'results_from_web_id';
    protected $fillable = [
        'school_result_id',
        'results',
    ];

    protected $casts = [
        'results' => 'array'
    ];
}
