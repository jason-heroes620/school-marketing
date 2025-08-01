<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Themes extends Model
{
    protected $table = 'themes';
    protected $primaryKey = 'theme_id';
    protected $fillable = [
        'school_result_id',
        'theme'
    ];
    public $timestamps = false;
}
