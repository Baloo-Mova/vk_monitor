<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


/**
 * App\Models\Proxies
 *
 * @property int $id
 * @property string $proxy
 * @property string $login
 * @property string $password

 */
class Proxies extends Model
{
    public $timestamps = true;
    public $table = "proxies";

    public $fillable = [
        'id',
        'proxy',
        'login',
        'password',
        'valid',
        'created_at',
        'updated_at'
    ];


}
