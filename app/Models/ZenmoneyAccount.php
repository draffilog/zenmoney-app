<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZenmoneyAccount extends Model
{
    protected $fillable = [
        'name',
        'code_zenmoney_account'
    ];
}

