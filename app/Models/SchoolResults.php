<?php

namespace App\Models;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsStringable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;


class SchoolResults extends Model
{
    use HasUuids;

    protected $table = 'school_results';
    protected $primaryKey = 'school_result_id';
    protected $fillable = [
        'name',
        'place_id',
        'school_account_id',
        'school_result_status',
        'radius',
        'geometry',
        'rating',
        'run_crawl',
        'is_main'
    ];

    protected $casts = [
        'geometry' => 'object', // or 'object'
    ];
}
