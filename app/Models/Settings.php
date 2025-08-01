<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    protected $table = 'settings';
    protected $primaryKey = 'setting_id';
    protected $fillable = [
        'setting_group_id',
        'setting',
        'sort_order',
        'status'
    ];

    public $timestamps = false;
}
