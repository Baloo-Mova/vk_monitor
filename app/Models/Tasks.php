<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


/**
 * App\Models\Tasks
 *
 * @property int $id
 * @property int $user_id
 * @property string $vk_link
 * @property string $find_query
 * @property datestamp $date_post_publication
 * @property int $notification_mode
 * @property int $telegram_id
 * @property string $email
 * @property int $reserved
 * @property int $checked
 */
class Tasks extends Model
{
    public $timestamps = true;
    public $table = "tasks";

    public $fillable = [
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
        'created_at'
    ];


}
