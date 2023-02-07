<?php

namespace App\Services;

use App\Models\Keyword;
use App\Models\Position;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use Illuminate\Support\Arr;

class DataForSeoService
{
    const API_USER = 'eugen.bogdanovich@gmail.com';
    const API_PASSWORD = '92becf55e97db178';

    const URL_NEW_TASK = 'https://api.dataforseo.com/v3/serp/google/organic/task_post';
    const URL_CHECK_TASK = 'https://api.dataforseo.com/v3/serp/google/organic/task_get/regular/%s';

    const TASK_STATUS_COMPLETE = 20000;
    const TASK_STATUS_NOT_FOUND = 40401;
    const TASK_STATUS_CREATED = 20100;

    public static function queueKeywordRankingTasks(array $keywords): array {
        $payload = [];
        foreach ($keywords as $keyword) {
            $payload[] = [
                "language_name" => 'English',
                "location_name" => 'United States',
                "keyword" => $keyword,
            ];
        }

        $outcome = [];
        foreach (array_chunk($payload, 100) as $chunk) {
            $response = self::getClient()->post(self::URL_NEW_TASK, [
                'json' => $chunk,
            ]);

            $result = json_decode($response->getBody(), true);
            foreach ($result["tasks"] as $task) {
                $remoteKeyword = $task["data"]["keyword"];
                if($task['status_code'] === self::TASK_STATUS_CREATED) {
                    $outcome[$remoteKeyword] = $task['id'];
                }
            }
        }

        return $outcome;
    }

    public static function getClient()
    {
        return new Client([
            'auth' => [
                self::API_USER,
                self::API_PASSWORD,
            ]
        ]);
    }

    public static function checkPendingTasks(array $taskIds, string $website)
    {
        $client = self::getClient();

        $promises = [];
        foreach ($taskIds as $taskId) {
            $url = sprintf(self::URL_CHECK_TASK, $taskId);
            $promises[$taskId] = $client->getAsync($url);
        }

        $outcome = [];
        foreach (array_chunk($promises, 20, true) as $chunk) {
            $responses = Promise\all($chunk)->wait();

            foreach ($responses as $taskId => $response) {
                if($response->getStatusCode() == 200) {
                    $outcome[$taskId] = self::processResponse(json_decode($response->getBody(), true), $website);
                }
            }
        }

        return $outcome;
    }

    public static function processResponse(array $result, string $websiteUrl)
    {
        if($result["tasks"][0]["status_code"] === self::TASK_STATUS_NOT_FOUND
            && isset($result["tasks"][0]["id"])
        ) {
            return;
        }

        if($result["tasks"][0]["status_code"] !== self::TASK_STATUS_COMPLETE) { return; }

        foreach ($result["tasks"][0]["result"][0]["items"] as $item) {
            if(str_contains($item['url'], $websiteUrl)) {
                return [
                    'url' => $item['url'],
                    'title' => $item['title']
                ];
            }
        }

        return null;
    }
}
