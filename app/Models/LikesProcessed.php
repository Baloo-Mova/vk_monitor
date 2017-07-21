<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LikesProcessed extends Model
{
    public    $fillable   = [
        'vk_id',
        'task_id'
    ];
    public    $timestamps = false;
    protected $table      = "likes_processed";
}
