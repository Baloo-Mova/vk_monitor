<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\Telegram;

class TelegramUpdates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:updates';

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
        try{
            $telegram = new Telegram();
            while(true){
                $telegram->getUpdates();
                sleep(3);
            }
        }catch(\Exception $ex){
            return $ex;
        }

    }
}
