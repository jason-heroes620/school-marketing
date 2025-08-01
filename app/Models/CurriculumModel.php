<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurriculumModel extends Model
{
    protected $table = 'curriculum_models';
    protected $primaryKey = 'curriculum_model_id';
    protected $fillable = [
        'school_result_id',
        'curriculum_model'
    ];
    public $timestamps = false;
}
