<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherStudentRatio extends Model
{
    protected $table = 'teacher_student_ratios';
    protected $primaryKey = 'teacher_student_ratio_id';
    protected $fillable = [
        'teacher_student_ratio_id',
        'school_result_id',
        'teacher_student_ratio',
        'sort_order'
    ];

    public $timestamps = false;
}
