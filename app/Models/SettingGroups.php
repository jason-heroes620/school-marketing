<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SettingGroups extends Model
{
    use HasUuids;

    protected $table = 'setting_groups';
    protected $primaryKey = 'setting_group_id';
    protected $fillable = [
        'setting_group',
        'setting_group_short',
        'sort_order',
        'setting_type',
        'has_other',
        'status'
    ];
    public $timestamps = false;
}
