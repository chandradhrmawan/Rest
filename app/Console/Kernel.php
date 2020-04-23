<?php

namespace App\Console;

use Illuminate\Support\Facades\DB;
use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
      $schedule->call('App\Helper\PlgConnectedExternalApps@clearScheduler');
      $schedule->call('App\Helper\ConnectedExternalApps@sendNotifToIBISQA')->everyMinute();
      $schedule->call('App\Helper\ConnectedExternalApps@sendNotifToIBISQA')->everyMinute();
      $schedule->call('App\Helper\PlgConnectedExternalApps@flagRealisationRequest');
      $schedule->call('App\Helper\PlgConnectedExternalApps@getUpdatePlacement');
      $schedule->call('App\Helper\PlgConnectedExternalApps@getUpdateRename');
    }
}
