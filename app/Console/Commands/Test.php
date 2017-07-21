<?php

namespace App\Console\Commands;

use App\Helpers\Emails;
use App\Models\AccountsData;
use Illuminate\Console\Command;
use App\Helpers\Telegram;

class Test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $from   = AccountsData::where(['valid' => 1])->first();
        $params = [
            'from'    => $from,
            'to'      => ['sergious-91@mail.ru'],
            'message' => [
                'subject' => 'Уведомление от ВК монитора',
                'body'    => "В паблике https://vk.com/public144979798 не обнаружен пост с ключевыми словами: котэ. Плановое время выхода поста - 2017-06-30 09:18:00"
            ]
        ];
        $mail   = new Emails($params);
        $mail->sendMessage();

        //$tele = new Telegram();
        //$telegramSended = $tele->sendMessage("232275585", "В паблике https://vk.com/public144979798 не обнаружен пост с ключевыми словами: котэ. Плановое время выхода поста - 2017-06-30 09:18:00");
    }
}
