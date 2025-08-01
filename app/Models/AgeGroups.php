<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgeGroups extends Model
{
    protected $table = 'age_groups';
    protected $primaryKey = 'age_group_id';
    protected $fillable = [
        'school_result_id',
        'age_group'
    ];
    public $timestamps = false;
}
