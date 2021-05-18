<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Mruser extends Model
{
    //
    protected   $table      = 'mrusers';
    protected   $primaryKey = 'id';

    protected $fillable = [
        'email',
        'password',
        'security',
    ];

    public function dropdowns() {
        return $this->hasMany('App\Mrdropdown', 'mruser_id', 'id');
    }
}
