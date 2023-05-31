<?php

namespace ZekyWolf\LGSQ\Protocols;

use ZekyWolf\LGSQ\Params\{
    EServerParams as SParams,
    EConnectionParams as CParams
};

class Query38
{
    public static function get(&$server, &$lgsl_need, &$lgsl_fp) // Terraria
    {
        if (!$lgsl_fp) {
            return false;
        }

        curl_setopt($lgsl_fp, CURLOPT_URL, "http://{$server[SParams::BASIC][CParams::IP]}:{$server[SParams::BASIC][CParams::QPORT]}/v2/server/status?players=true");
        $buffer = curl_exec($lgsl_fp);
        $buffer = json_decode($buffer, true);

        if ($buffer['status'] != '200') {
            $server[SParams::CONVARS]['_error']    = $buffer['error'];
            return false;
        }

        $server[SParams::SERVER]['name']        = $buffer['name'];
        $server[SParams::SERVER]['map']         = $buffer['world'];
        $server[SParams::SERVER]['players']     = $buffer['playercount'];
        $server[SParams::SERVER]['playersmax']  = $buffer['maxplayers'];
        $server[SParams::SERVER]['password']    = $buffer['serverpassword'];
        $server[SParams::CONVARS]['uptime']      = $buffer['uptime'];
        $server[SParams::CONVARS]['version']     = $buffer['serverversion'];

        return true;
    }
}
