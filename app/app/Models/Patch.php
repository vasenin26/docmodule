<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Patch extends Model
{
    protected $fillable = [
        'target',
        'target_id',
        'title',
        'content',
    ];
}
