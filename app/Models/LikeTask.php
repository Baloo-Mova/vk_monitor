<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LikeTask extends Model
{
    protected $table = 'like_tasks';
    public $fillable =[
        'id',
        'user_id',
        'vk_link',
        'find_query',
        'date_post_publication',
        'notification_mode',
        'telegram_id',
        'email',
        'reserved',
        'checked',
        'created_at',
        'likes_number',
        'api_id',
        'api_key'
    ];
}
