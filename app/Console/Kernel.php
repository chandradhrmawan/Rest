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
      $schedule->call('App\Helper\Npks\ConnectedExternalAppsNPKS@clearScheduler');
      $schedule->call('App\Helper\Npk\ConnectedExternalAppsNPK@sendNotifToIBISQA')->everyMinute();
      $schedule->call('App\Helper\Npk\ConnectedExternalAppsNPK@sendNotifToIBISQA')->everyMinute();
      $schedule->call('App\Helper\Npks\ConnectedExternalAppsNPKS@flagRealisationRequest');
      $schedule->call('App\Helper\Npks\ConnectedExternalAppsNPKS@getUpdatePlacement');
      $schedule->call('App\Helper\Npks\ConnectedExternalAppsNPKS@getUpdateRename');
    }
}
