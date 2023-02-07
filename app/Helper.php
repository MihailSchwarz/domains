<?php

namespace App;

use Illuminate\Support\Facades\Log;

class Helper
{

    public static $command;
    public static array $state = [];

    public static function words(string $text): array
    {
        return explode(' ', $text);
    }

    public static function wordsCount(string $text): int
    {
        return count(explode(' ', $text));
    }

    public static function strpos_arr($haystack, $needle)
    {
        if (!is_array($needle)) $needle = array($needle);
        foreach ($needle as $what) {
            if (($pos = strpos($haystack, $what)) !== false) return $what;
        }
        return false;
    }

    public static function isTextCaps(string $text)
    {
        $text = preg_replace('/\W/', '', $text);
        $words = explode(' ', $text);
        $qtyCaps = 0;
        foreach ($words as $word) {
            if (ctype_upper($word)) {
                $qtyCaps++;
            }
        }

        return ($qtyCaps / count($words)) > 0.3;
    }

    public static function deCapitalizeText(string $text)
    {
        $sentences = explode('. ', $text);
        foreach ($sentences as &$sentence) {
            $sentence = ucfirst(mb_strtolower($sentence));
        }

        $sentences[0] = ucfirst($sentences[0]);
        return implode('. ', $sentences);
    }

    public static function initCommandLogger($command)
    {
        self::$command = $command;
    }

    public static function log(...$args)
    {
        if(self::$command) {
            self::$command->info(sprintf(...$args));
        }

        Log::channel(self::getState('queue') ?: 'single')
            ->info(sprintf(...$args));
    }

    public static function csvToArray($filepath)
    {
        $csvRows = array_map('str_getcsv', file($filepath));
        $csvHeader = array_shift($csvRows);
        $data = [];
        foreach ($csvRows as $row) {
            $data[] = array_combine($csvHeader, $row);
        }

        return $data;
    }

    public static function arrayToCsv($file_name, $arr)
    {
        $has_header = false;

        foreach ($arr as $c) {

            $fp = fopen($file_name, 'a');

            if (!$has_header) {
                fputcsv($fp, array_keys($c));
                $has_header = true;
            }

            fputcsv($fp, $c);
            fclose($fp);
        }
    }

    public static function getTextSentences($text)
    {
        return preg_split('/(?<!Mr.|Mrs.|Ms.|Dr.|St.|\s[A-Z].|Ph.D.|Ph.)(?<=[.?!;])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
    }

    public static function getSqlWithBindings($query)
    {
        return vsprintf(str_replace('?', '%s', $query->toSql()), collect($query->getBindings())->map(static fn ($binding) => is_numeric($binding) ? $binding : "'{$binding}'")->toArray());
    }

    public static function getLinesFromText(string $text)
    {
        return collect(explode("\n", $text))
            ->map(function ($line) {
                return rtrim(trim(str_replace('#', '', $line)), '.');
            })
            ->filter(function ($line) {
                return (bool)trim($line);
            })
            ->values()
            ->toArray();
    }

    public static function convertToUTF8($text){

        $encoding = mb_detect_encoding($text, mb_detect_order(), false);

        if($encoding == "UTF-8")
        {
            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        }


        return iconv(mb_detect_encoding($text, mb_detect_order(), false), "UTF-8//IGNORE", $text);
    }

    public static function setState(string $key, $value)
    {
        self::$state[$key] = $value;
    }

    public static function getState(string $key)
    {
        return self::$state[$key] ?? null;
    }

    public static function logError(\Throwable $e)
    {
        Log::channel(self::getState('queue') ?: 'single')
            ->error($e);
    }

    public static function getRandomString(int $count)
    {
        $base = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_-';
        $ret = '';
        $strlen = \strlen($base);
        for ($i = 0; $i < $count; ++$i) {
            $ret .= $base[random_int(0, $strlen - 1)];
        }

        return $ret;
    }
}
