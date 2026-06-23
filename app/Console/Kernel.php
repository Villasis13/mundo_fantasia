<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Backup diario a las 3:30 AM
        $schedule->command('backup:database')->dailyAt('04:00');
        // Envío de boletas por resumen diario - facturas directas — múltiples horarios
//        $schedule->command('sunat:enviar-comprobantes')->dailyAt('22:38');
//        $schedule->command('sunatFac:enviar-comprobantes')->dailyAt('22:38');
        $schedule->command('sunat:enviar-comprobantes')->dailyAt('23:00');
        $schedule->command('sunatFac:enviar-comprobantes')->dailyAt('23:30');
        $schedule->command('sunat:enviar-comprobantes')->dailyAt('23:55');
        $schedule->command('sunatFac:enviar-comprobantes')->dailyAt('00:01');
        $schedule->command('sunat:enviar-comprobantes')->dailyAt('00:30');
        $schedule->command('sunatFac:enviar-comprobantes')->dailyAt('01:00');
        $schedule->command('sunat:enviar-comprobantes')->dailyAt('02:00');
        $schedule->command('sunatFac:enviar-comprobantes')->dailyAt('03:00');

    }

    /**
     * Register the commands for the application.
     *
     *
     *
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
