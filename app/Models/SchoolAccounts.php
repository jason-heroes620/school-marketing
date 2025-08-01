<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SchoolAccounts extends Model
{
    use HasUuids;

    protected $table = 'school_accounts';
    protected $primaryKey = 'school_account_id';
    protected $fillable = [
        'user_id',
        'name',
        'school_address',
        'latitude',
        'longitude',
    ];
    public $timestamps = false;
}
