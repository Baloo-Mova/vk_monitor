<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ViewTask extends Model
{
    public $timestamps = true;
    public $table = "view_tasks";

    public $fillable = [
        'id',
        'user_id',
        'vk_link',
        'notification_mode',
        'telegram_id',
        'email',
        'reserved',
        'checked',
        'created_at'
    ];
}
