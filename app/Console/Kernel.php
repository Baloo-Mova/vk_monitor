<?php

namespace App\Console;

use App\Console\Commands\LikeMonitor;
use App\Console\Commands\ViewMonitor;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

use App\Console\Commands\VKMonitor;
use App\Console\Commands\NotificationSender;
use App\Console\Commands\Test;
use App\Console\Commands\TelegramUpdates;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        VKMonitor::class,
        NotificationSender::class,
        Test::class,
        TelegramUpdates::class,
        LikeMonitor::class,
        ViewMonitor::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        require base_path('routes/console.php');
    }
}
