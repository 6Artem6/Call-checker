<?php

namespace App\Console;

use App\Console\Commands\CallAnalysisCronController;
use App\Console\Commands\CallTranscribeCronController;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Регистрация всех пользовательских команд в приложении.
     *
     * @var array
     */
    protected $commands = [
        // Регистрация вашей команды
        CallTranscribeCronController::class,
        CallAnalysisCronController::class,
    ];

    /**
     * Определение расписания для команд.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Пример для cron задачи, которая будет выполняться каждую минуту
        $schedule->command('cron:run')->everyMinute();
    }

    /**
     * Регистрируем все задачи Artisan.
     *
     * @param  \Illuminate\Foundation\Console\Application  $app
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
