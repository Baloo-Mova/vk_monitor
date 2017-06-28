<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Notifications
 *
 * @property int $id
 * @property string $login
 * @property string $password
 * @property int $type
 * @property string $smtp_address
 * @property int $smtp_port
 * @property int $valid
 */
class AccountsData extends Model
{
    public $timestamps = true;
    public $table = "accounts_data";

    public $fillable = [
        'login',
        'password',
        'type',
        'smtp_address',
        'smtp_port',
        'valid',
        'created_at',
        'updated_at',

    ];


}
