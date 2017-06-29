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
        $from = AccountsData::where(['valid' => 1])->first(); 
        $params = [
            'from'    => $from,
            'to'      => ['sergious91@gmail.com'],
            'message' => [
                'subject' => 'Уведомление от ВК монитора',
                'body'    => "test"
            ]
        ];

        $mailSender  = new Emails($params);
        $emailSended = $mailSender->sendMessage();
    }
}
