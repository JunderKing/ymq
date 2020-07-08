<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class AdminLearn implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $tries = 1;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        \LogUtil::info('construct');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        \LogUtil::info('start');
        sleep(10);
        \LogUtil::info('end');
        // throw new \Exception('TestError');
    }
}
