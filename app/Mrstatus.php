<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Mrstatus extends Model
{
    protected   $table      = 'mrstatuses';
    protected   $primaryKey = 'id';

    protected $fillable = [
        'mr_userId',
        'mr_id',
        'name',
        'status',
        'anchor_url',
        'progress'
    ];
}
