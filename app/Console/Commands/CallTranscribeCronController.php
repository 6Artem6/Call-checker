<?php

namespace App\Console\Commands;

use App\Http\Controllers\CronController;
use Illuminate\Console\Command;

class CallTranscribeCronController extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transcribe-cron:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Выполнить метод из CronController';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Создаём экземпляр контроллера
        $controller = new CronController();

        // Вызов нужного метода
        $controller->fileTranscribe(); // Замените `yourMethodName` на ваш метод

        $this->info('Метод выполнен успешно.');
    }
}
