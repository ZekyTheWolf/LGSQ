<?php

namespace ZekyWolf\LGSQ\Protocols;

use ZekyWolf\LGSQ\Params\{
    EServerParams as SParams,
    ERequestParams as RParams,
    EConnectionParams as CParams
};

class Query37
{
    public static function get(&$server, &$lgsl_need, &$lgsl_fp) // SCUM API
    {
        if (!$lgsl_fp) {
            return false;
        }

        $lgsl_need[RParams::CONVARS] = false;
        $lgsl_need[RParams::PLAYERS] = false;

        curl_setopt($lgsl_fp, CURLOPT_URL, "https://api.hellbz.de/scum/api.php?address={$server[SParams::BASIC][CParams::IP]}&port={$server[SParams::BASIC][CParams::PORT]}");
        $buffer = curl_exec($lgsl_fp);
        $buffer = json_decode($buffer, true);

        if (!$buffer['success']) {
            return false;
        }

        $lgsl_need[SParams::SERVER] = false;

        $server[SParams::BASIC]['name']        = $buffer['data'][0]['name'];
        $server[SParams::BASIC]['map']         = "SCUM";
        $server[SParams::BASIC]['players']     = $buffer['data'][0]['players'];
        $server[SParams::BASIC]['playersmax']  = $buffer['data'][0]['players_max'];
        $server[SParams::CONVARS]['time']        = $buffer['data'][0]['time'];
        $server[SParams::CONVARS]['version']     = $buffer['data'][0]['version'];

        return true;
    }
}
