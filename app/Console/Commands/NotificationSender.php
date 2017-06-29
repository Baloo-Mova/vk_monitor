<?php

namespace App\Console\Commands;

use App\Helpers\Telegram;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PHPMailer;

use App\Models\Notifications;
use App\Models\AccountsData;
use App\Helpers\Emails;

class NotificationSender extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'send a notifications message';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public $content;

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
        //
        try {
            while (true) {
                $from = AccountsData::where(['valid' => 1])->first();
                if (!isset($from)) {
                    sleep(10);
                    continue;
                }
                $this->content['notifications'] = null;
                DB::transaction(function () {

                    $collection = Notifications::where(['reserved' => 0])->first();   //для тестов

                    if (!isset($collection)) {
                        return;
                    }

                    $collection->reserved = 1;
                    $collection->save();

                    $this->content['notifications'] = $collection;
                });
                $notifications = $this->content['notifications'];
                if (!isset($notifications)) {
                    sleep(5);
                    continue;
                }

                $emailSended = false;
                $telegramSended = false;

                if (isset($notifications->email)) {
                    $params = [
                        'from' => $from,
                        'to' => [$notifications->email],
                        'message' => [
                            'subject' => 'Уведомление от ВК монитора',
                            'body' => $notifications->message
                        ]
                    ];
                    $mailSender = new Emails($params);
                    $emailSended = $mailSender->sendMessage();
                    echo "send email".PHP_EOL;
                } else {
                    $emailSended = true;
                }


                if (isset($notifications->telegram_id)) {
                    $tele = new Telegram();
                    $telegramSended = $tele->sendMessage($notifications->telegram_id, $notifications->message);
                    echo "send telegram".PHP_EOL;
                } else {
                    $telegramSended = true;
                }

                if ($telegramSended && $emailSended) {
                    $notifications->delete();
                }
            }
        } catch (\Exception $ex) {

        }
    }
}
