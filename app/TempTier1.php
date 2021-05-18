<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TempTier1 extends Model
{
    //
    protected   $table      = 'temp_tier1s';
    protected   $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'title',
        'anchor_url',
        'text',
        'completed_percent',
    ];


    public function Links () {

        return $this->hasMany('App\TempTier1Link', 'temptier1_id');

    }

}
