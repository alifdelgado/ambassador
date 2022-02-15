<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class UpdateRankingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:rankings';

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
     * @return int
     */
    public function handle()
    {
        $ambassadors = User::ambassador()->get();
        $bar = $this->output->createProgressBar($ambassadors->count());
        $bar->start();
        $ambassadors->each(function(User $user) use ($bar) {
            Redis::zadd('rankings', (int)$user->revenue, $user->name);
            $bar->advance();
        });
        $bar->finish();
    }
}
