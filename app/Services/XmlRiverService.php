<?php

namespace App\Services;

use App\Models\Keyword;
use App\Models\Position;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Pdp\Rules;
use Pdp\Domain;

class XmlRiverService
{
    const API_USER = '8355';
    const API_PASSWORD = '6969953de87cc1f8e38263af814b7ab2aa3da8b0';

    const URL_NEW_TASK = 'http://xmlriver.com/search/xml';
    const ALLOWED_DOMAINS = [
        'com',
        'net',
        'org',
        'de',
        'it',
        'nl',
        'es',
        'fr',
        'me',
        'ca',
        'io',
        'info',
        'co.uk',
        'com.au',
    ];

    public static function getSerp(Keyword $keyword) {
        $client = new Client();

        $response = $client->get(self::URL_NEW_TASK, ['query' => [
            'user' => self::API_USER,
            'key' => self::API_PASSWORD,
            'query' => $keyword->keyword
        ]]);

        $xml = $response->getBody()->__toString();
        $result = json_decode(json_encode(simplexml_load_string($xml)),true);

        if(empty($result["response"]["results"])) {
            return;
        }

        $results = $result["response"]["results"]["grouping"]["group"];

        $urls = Arr::pluck($results, 'doc.url');

        $domains = [];
        $publicSuffixList = Rules::fromPath(Storage::path('public_suffix_list.dat'));

        foreach ($urls as $key => $url) {

            $url = str_replace('https://', '', $url);
            $url = str_replace('http://', '', $url);

            $exploded = explode('/', $url);
            $domain = $exploded[0];

            try {
                $domain = Domain::fromIDNA2008($domain);
                $resolved = $publicSuffixList->resolve($domain);
                $domainString = $resolved->registrableDomain()->toString();

                $suffix = $resolved->suffix()->toString();
                if(!in_array($suffix, self::ALLOWED_DOMAINS)) {
                    echo "missing domain $domainString\n";
                    continue;
                }

                $domains[$key] = $domainString;
            } catch (\Throwable) {

                echo "skipping $domain";
                continue;
            }
        }

        $existingDomains = \App\Models\Domain::query()
            ->whereIn('domain', $domains)
            ->get()
            ->keyBy('domain');

        $payload = [];
        foreach ($domains as $key => $domain) {
            if(!$existingDomains->get($domain)) {
                $payload[] = [
                    'domain' => $domain,
                    'search_query' => $keyword->keyword,
                    'position' => $key + 1,
                ];
            }
        }

        \App\Models\Domain::query()
            ->insert($payload);
    }
}
