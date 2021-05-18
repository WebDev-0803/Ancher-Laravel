<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Mrcampaign extends Model
{
    protected   $table      = 'mrcampaigns';
    protected   $primaryKey = 'id';

    protected $fillable = [
        'mruser_id',
        'site_url',
        'keywords',
        'title',
        'body',
        'body_files',
        'builder',
        'profile',
        'diagram',
        'accounts',
        'link_article',
        'category',
        'generic',
        'blog_title',
        'blog_addr',
        'youtube_url',
        'name',
        'run',
        'priority',
        'bulk',
        'client',
    ];
}
