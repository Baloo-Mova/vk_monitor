<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Notifications
 *
 * @property int $id
 * @property int $task_id
 * @property string $message

 */
class Notifications extends Model
{
    public $timestamps = true;
    public $table = "notifications";

    public $fillable = [
        'id',
        'task_id',
        'message',
        'created_at',
        'updated_at'
    ];


}
