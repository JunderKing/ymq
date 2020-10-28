<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PHPSocketIO\SocketIO;
use Workerman\Worker;

class LearnSocket extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'learn:socket {action} {--d}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        global $argv;
        $action = $this->argument('action');
        $argv[0] = 'wk';
        $argv[1] = $action;
        $argv[2] = $this->option('d') ? '-d' : '';
        $io = new SocketIO(9001);
        self::start($io);
        Worker::runAll();
    }

    public static function start($io) {
        $io->on('connection', function($socket) use ($io) {
            $token = $socket->handshake['query']['token'];
            var_dump('connection: ' . $token);
            $socket->emit('serverMsg', 'hello');
            $socket->on('clientMsg', function($msg, $output = null) use ($socket, $io) {
                var_dump('clientMsg: ' . $msg);
                $msg = json_decode($msg, true);
                if ($output) {
                    $output(['errcode' => 0, 'errmsg' => '执行成功', 'data' => $msg]);
                }
            });
        });
        // 监听内部消息
        $io->on('workerStart', function() use ($io) {
            $innerWorker = new Worker('text://127.0.0.1:5678');
            $innerWorker->onMessage = function($inner, $data) use ($io) {
                $inner->send('ok');
                // $io->to('hello')->emit('updateCurve', $data);
                var_dump($data);
                $io->emit('serverMsg', $data);
            };
            $innerWorker->listen();
        });
    }
}
