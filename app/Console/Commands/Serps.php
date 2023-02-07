<?php

namespace App\Console\Commands;

use App\Helper;
use App\Models\Keyword;
use App\Services\DataForSeoService;
use App\Services\XmlRiverService;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class Serps extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'serps';

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
        $keywords = Keyword::query()
            ->where('completed', 0)
            ->get();

        foreach ($keywords as $keyword) {
            XmlRiverService::getSerp($keyword);
            $keyword->completed = 1;
            $keyword->save();

            $this->info("handled keyword {$keyword->keyword}");
        }

        return Command::SUCCESS;
    }
}
