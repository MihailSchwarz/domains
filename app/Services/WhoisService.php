<?php

namespace App\Services;


use App\Models\Domain;
use Carbon\Carbon;
use Iodev\Whois\Factory;

class WhoisService {

    public static function whois($domains)
    {
        $whois = Factory::get()->createWhois();

        $start = now();
        $i = 0;

        /** @var Domain $domain */
        foreach ($domains as $domain) {

            $info = null;

            try {
                $info = $whois->loadDomainInfo($domain->domain);
            } catch (\Exception $exception) {
                $domain->no_expiration_date = true;
                $domain->save();
                echo "exception on trying {$domain->domain}, exception message {$exception->getMessage()}\n";
                continue;
            }

            if(!$info) {
                echo "no info for {$domain->domain}\n";
                $domain->no_expiration_date = true;
                $domain->save();
                continue;
            }

            if(!$info->expirationDate) {
                $domain->no_expiration_date = true;
                $domain->save();
                continue;
            }

            $expDate = new Carbon($info->expirationDate);
            $domain->expires_at = $expDate;
            $domain->save();

            $i++;
        }

        $diff = now()->diffInMilliseconds($start) / 1000;

        $qty = count($domains);

        echo "$i out of $qty\n";
        echo "$diff sec\n";
    }
}
