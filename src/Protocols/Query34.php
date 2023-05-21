<?php

namespace ZekyWolf\LGSQ\Protocols;

use ZekyWolf\LGSQ\Helpers\{
    EServerParams as SParams,
    ERequestParams as RParams,
    EConnectionParams as CParams
};

class Query34
{
    public static function get(&$server, &$lgsl_need, &$lgsl_fp) // Rage:MP
    {
        if (!$lgsl_fp) {
            return false;
        }

        $lgsl_need[RParams::CONVARS] = false;
        $lgsl_need[RParams::PLAYERS] = false;

        curl_setopt($lgsl_fp, CURLOPT_URL, 'https://cdn.rage.mp/master/');
        $buffer = curl_exec($lgsl_fp);
        $buffer = json_decode($buffer, true);

        if (isset($buffer["{$server[SParams::BASIC][CParams::IP]}:{$server[SParams::BASIC][CParams::PORT]}"])) {
            $value = $buffer["{$server[SParams::BASIC][CParams::IP]}:{$server[SParams::BASIC][CParams::PORT]}"];
            $server[SParams::SERVER]['name']       = $value['name'];
            $server[SParams::SERVER]['map']        = "ragemp";
            $server[SParams::SERVER]['players']    = $value['players'];
            $server[SParams::SERVER]['playersmax'] = $value['maxplayers'];
            $server[SParams::CONVARS]['url']        = $value['url'];
            $server[SParams::CONVARS]['peak']       = $value['peak'];
            $server[SParams::CONVARS]['gamemode']   = $value['gamemode'];
            $server[SParams::CONVARS]['lang']       = $value['lang'];
            return true;
        }

        return false;
    }
}
