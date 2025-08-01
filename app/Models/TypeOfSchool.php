<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TypeOfSchool extends Model
{
    protected $table = 'type_of_school';
    protected $primaryKey = 'id';
    protected $fillable = [
        'school_result_id',
        'type_of_school'
    ];

    public $timestamps = false;
}
