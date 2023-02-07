<?php

namespace App\Console\Commands;

use App\Models\Domain;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

class AvailableDomains extends Command
{

    const API_KEY = '9uJBeQUWgNV_5BuJumrkDw55Mwz78ZDiXG';
    const API_SECRET = '7icFhJmhto7zE8uWxEfZZs';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'avail';

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
        $domains = Domain::query()
            ->where('expires_at', '<', now())
            ->whereNull('checked_availability_at')
            ->limit(10)
            ->get()
            ->keyBy('domain');

        $payload = $domains->pluck('domain')->toArray();
        if(!count($payload)) {
            $this->error('no domains to check');

            return;
        }

        $client = new Client();
        $response = $client->post('https://api.godaddy.com/v1/domains/available?checktype=full', [
            'json' => $payload,
            'headers' => [
                'Authorization' => sprintf('sso-key %s:%s', self::API_KEY, self::API_SECRET)
            ]
        ]);

        $result = json_decode($response->getBody()->__toString(), true);

        if(!empty($result["domains"])) {
            foreach ($result['domains'] as $payload) {
                $model = $domains->get($payload['domain']);
                $model->available = $payload['available'];
                $model->checked_availability_at = now();
                $model->save();

                if($payload['available']) {
                    $this->info("{$payload['domain']} is available");
                } else {
                    $this->error("{$payload['domain']} is not available");
                }
            }
        }

        if(!empty($result["errors"])) {
            foreach ($result["errors"] as $payload) {
                $model = $domains->get($payload['domain']);
                $model->available = false;
                $model->checked_availability_at = now();
                $model->error = $payload['code'];
                $model->save();

                $this->error("{$payload['domain']} is not available");
            }
        }

        return Command::SUCCESS;
    }
}
