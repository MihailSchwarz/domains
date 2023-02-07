<?php

namespace App\Console\Commands;

use App\Helper;
use App\Models\Keyword;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class ImportKeywords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'keywords';

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
        $path = Storage::path('keywords.csv');
        $data = Helper::csvToArray($path);

        $keywords = Arr::pluck($data, 'Keyword');
        $positions = Arr::pluck($data, 'Position');


        $payload = [];
        foreach ($keywords as $key => $keyword) {
            $payload[] = [
                'keyword' => $keyword,
                'position' => $positions[$key]
            ];
        }

        Keyword::query()
            ->insert($payload);

        return Command::SUCCESS;
    }
}
