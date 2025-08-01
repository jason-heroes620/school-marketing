<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EducationPhilosophy extends Model
{
    protected $table = 'education_philosophy';
    protected $primaryKey = 'id';
    protected $fillable = [
        'school_result_id',
        'education_philosophy'
    ];

    public $timestamps = false;
}
