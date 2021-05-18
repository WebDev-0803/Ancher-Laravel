<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Mrdropdown extends Model
{
    protected   $table      = 'mrdropdowns';
    protected   $primaryKey = 'id';

    protected $fillable = [
        'mruser_id',
        'category',
        'option',
    ];

    public function mruser() {
        return $this->belongsTo('App\Mruser');
    }
}
