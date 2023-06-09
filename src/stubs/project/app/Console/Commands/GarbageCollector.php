<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Pipeline\Pipeline;

class GarbageCollector extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gc:cleanup
            {--t|type=* : To clean only type}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cleanup';


    public $refComponents = null; //for the interface

    public static $cleaning = [];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        static::$cleaning = [
            'sessions' => function($command, \Closure $next) {
                $command->components->task("Cleaning sessions", function() use ($command){
                    return $command->sessionGC();

                });
                return $next($command);
            },
        ];

        // @HOOK_CLEANING
    }

    public function sessionGC() {
        session()->getHandler()->gc(
            (config('session.lifetime') ?? null) * 60 //to seconds
        );
        return true;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        if($types = $this->option('type')) {
            static::$cleaning = array_intersect_key(static::$cleaning, array_flip($types));
        }
        app(Pipeline::class)
            ->send($this)
            ->through(static::$cleaning)
            ->then(function($command){
                $this->components->info("Done!");
            });
    }
}
