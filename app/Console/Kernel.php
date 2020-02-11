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
      $schedule->call(function () {
        $database    = DB::connection('omuster')->table('TM_USER')->where("USER_LOGIN","1")->get();
        foreach ($database as $data) {
          $time    = date('H:i:s',strtotime('+7 hour',strtotime(date("h:i:s"))));
          $active  = intval(strtotime($data->user_active));
          $now     = intval(strtotime($time));
          $selisih = ($now - $active)/60;
          if ($selisih >= 240) {
            $user[] = [$data->user_name, $selisih];
             DB::connection('omuster')->table('TM_USER')->where('USER_ID', $data->user_id)->update(["USER_LOGIN" => "", "API_TOKEN" => ""]);
          }
        }
      })->hourly();

      $schedule->call('App\Helper\ConnectedExternalApps@sendNotifToIBISQA')->everyMinute();
      $schedule->call('App\Helper\PlgConnectedExternalApps@getRealGati');
      $schedule->call('App\Helper\PlgConnectedExternalApps@getRealStuffing');
      $schedule->call('App\Helper\PlgConnectedExternalApps@getRealStripping');
      $schedule->call('App\Helper\PlgConnectedExternalApps@getUpdatePlacement');



    }
}
