<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TempTier1Link extends Model
{
    //
    protected   $table      = 'temp_tier1_links';
    protected   $primaryKey = 'id';

    protected $fillable = [        
        'url',
        'status',
        'anchor_text',
        'anchor_url',
        'file_size',
        'processed_at',
        'temptier1_id'
    ];


    public function Campaign() {

        return $this->belongsTo('App\TempTier1', 'temptier1_id');

    }
}
