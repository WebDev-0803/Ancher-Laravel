<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OhiStatus extends Model
{
    //
    protected   $table      = 'ohi_statuses';
    protected   $primaryKey = 'id';

    protected $fillable = [
        'for_process',
        'processed',
        'completed_at'
    ];

    protected $hidden = [
        'id'
    ];
}
