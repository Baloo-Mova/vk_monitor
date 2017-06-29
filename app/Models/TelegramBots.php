<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramBots extends Model
{
    protected $table = 'telegram_api';
    public $timestamps = false;
    public $fillable= [
        'bot_key',
        'offset'
    ];
}
