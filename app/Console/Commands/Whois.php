<?php

namespace App\Console\Commands;

use App\Models\Domain;
use App\Services\WhoisService;
use Illuminate\Console\Command;

class Whois extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whois';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $qty = 100;
        $domains = Domain::query()
            ->whereNull('expires_at')
            ->where('no_expiration_date', 0)
            ->limit($qty)
            ->get();

        WhoisService::whois($domains);

        $this->info("processed batch of $qty");
        $this->handle();
    }
}
