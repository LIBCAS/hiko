<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

use App\Console\Commands\AddTenant;
use App\Console\Commands\CreateTenant;
use App\Console\Commands\CreateTenantDirs;
use App\Console\Commands\MigrateCentral;
use App\Console\Commands\PrepareOldSqlImports;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        AddTenant::class,
        CreateTenant::class,
        CreateTenantDirs::class,
        MigrateCentral::class,
        PrepareOldSqlImports::class,
        // Add any new tenant-related commands here
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('metadata-digests:process')
            ->everyMinute()
            ->withoutOverlapping(30);

        $schedule->command('metadata-digests:dispatch-monthly')
            ->monthlyOn(
                config('metadata_digest.monthly_day', 1),
                config('metadata_digest.monthly_time', '00:10')
            )
            ->timezone(config('metadata_digest.timezone', config('app.timezone')))
            ->withoutOverlapping(180);
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        // Load commands from the Commands directory
        $this->load(__DIR__.'/Commands');

        // Include the console routes
        require base_path('routes/console.php');
    }
}
