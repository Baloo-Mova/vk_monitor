<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramAccounts extends Model
{
    public $timestamps = true;
    public $table = "telegram_accounts";

    public $fillable = [
        'id',
        'user_id',
        'chat_id',
    ];
}
