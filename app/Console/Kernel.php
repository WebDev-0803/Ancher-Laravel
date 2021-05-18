<?php

namespace App\Console;

use App\Setting;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
        'App\Console\Commands\UploadOHI',
        'App\Console\Commands\TierMonitoring',
        'App\Console\Commands\MoneyRobotToOhi',
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $settings = Setting::first();

        $schedule->command('upload:OHI')
            ->cron('*/' . $settings->cron_period_time . ' * * * *');
        $schedule->command('check:tiers')
            ->weekly();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
