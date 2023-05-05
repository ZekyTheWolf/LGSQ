<?php

namespace ZekyWolf\LGSQ\Protocols;

use ZekyWolf\LGSQ\Helpers\{
    Parse\Colors,
    EServerParams as SParams,
    ERequestParams as RParams
};

class Query35
{
    public static function get(&$server, &$lgsl_need, &$lgsl_fp) // FiveM / RedM
    {
        if (!$lgsl_fp) {
            return false;
        }

        curl_setopt($lgsl_fp, CURLOPT_URL, "http://{$server[SParams::BASIC]['ip']}:{$server[SParams::BASIC]['q_port']}/dynamic.json");
        $buffer = curl_exec($lgsl_fp);
        $buffer = json_decode($buffer, true);

        if (!$buffer) {
            return false;
        }

        $server[SParams::SERVER]['name'] = Colors::get($buffer['hostname'], 'fivem');
        $server[SParams::SERVER]['players'] = $buffer['clients'];
        $server[SParams::SERVER]['playersmax'] = $buffer['sv_maxclients'];
        $server[SParams::SERVER]['map'] = $buffer['mapname'];
        if ($server[SParams::SERVER]['map'] == 'redm-map-one') {
            $server[SParams::SERVER]['game'] = 'redm';
        }
        $server[SParams::CONVARS]['gametype'] = $buffer['gametype'];
        $server[SParams::CONVARS]['version'] = $buffer['iv'];

        if ($lgsl_need[RParams::PLAYERS]) {
            $lgsl_need[RParams::PLAYERS] = false;

            curl_setopt($lgsl_fp, CURLOPT_URL, "http://{$server[SParams::BASIC]['ip']}:{$server[SParams::BASIC]['q_port']}/players.json");
            $buffer = curl_exec($lgsl_fp);
            $buffer = json_decode($buffer, true);

            foreach($buffer as $key => $value) {
                $server[SParams::PLAYERS][$key]['name'] = $value['name'];
                $server[SParams::PLAYERS][$key]['ping'] = $value['ping'];
            }
        }

        return true;
    }
}
