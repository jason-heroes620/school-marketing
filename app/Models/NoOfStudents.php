<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NoOfStudents extends Model
{
    protected $table = 'no_of_students';
    protected $primaryKey = 'no_of_student_id';
    protected $fillable = [
        'no_of_student_id',
        'school_result_id',
        'no_of_student'
    ];
}
