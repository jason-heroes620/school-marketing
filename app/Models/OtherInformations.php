<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtherInformations extends Model
{
    protected $table = 'other_informations';
    protected $primaryKey = 'other_information_id';
    protected $fillable = [
        'school_result_id',
        'other_information'
    ];
    public $timestamps = false;
}
