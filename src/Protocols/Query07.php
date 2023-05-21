<?php

namespace ZekyWolf\LGSQ\Protocols;

use ZekyWolf\LGSQ\Helpers\{
    Parse\Colors,
    EServerParams as SParams,
    EConnectionParams as CParams
};

class Query07
{
    public static function get(&$server, &$lgsl_need, &$lgsl_fp)
    {
        //---------------------------------------------------------+

        fwrite($lgsl_fp, "\xFF\xFF\xFF\xFFstatus\x00");

        $buffer = fread($lgsl_fp, 4096);

        if (!$buffer) {
            return false;
        }

        //---------------------------------------------------------+

        $buffer = substr($buffer, 6, -2); // REMOVE HEADER AND FOOTER
        $part   = explode("\n", $buffer); // SPLIT INTO SETTINGS/PLAYER/PLAYER/PLAYER

        //---------------------------------------------------------+

        $item = explode("\\", $part[0]);

        foreach ($item as $item_key => $data_key) {
            if ($item_key % 2) {
                continue;
            } // SKIP ODD KEYS

            $data_key               = strtolower($data_key);
            $server[SParams::CONVARS][$data_key] = $item[$item_key+1];
        }

        //---------------------------------------------------------+

        array_shift($part); // REMOVE SETTINGS

        foreach ($part as $key => $data) {
            preg_match("/(.*) (.*) (.*) (.*) \"(.*)\" \"(.*)\" (.*) (.*)/s", $data, $match); // GREEDY MATCH FOR SKINS

            $server[SParams::PLAYERS][$key]['pid']         = $match[1];
            $server[SParams::PLAYERS][$key]['score']       = $match[2];
            $server[SParams::PLAYERS][$key]['time']        = $match[3];
            $server[SParams::PLAYERS][$key]['ping']        = $match[4];
            $server[SParams::PLAYERS][$key]['name']        = Colors::get($match[5], $server[SParams::BASIC][CParams::TYPE]);
            $server[SParams::PLAYERS][$key]['skin']        = $match[6];
            $server[SParams::PLAYERS][$key]['skin_top']    = $match[7];
            $server[SParams::PLAYERS][$key]['skin_bottom'] = $match[8];
        }

        //---------------------------------------------------------+

        $server[SParams::SERVER]['game']       = $server[SParams::CONVARS]['*gamedir'];
        $server[SParams::SERVER]['name']       = $server[SParams::CONVARS]['hostname'];
        $server[SParams::SERVER]['map']        = $server[SParams::CONVARS]['map'];
        $server[SParams::SERVER]['players']    = $server[SParams::PLAYERS] ? count($server[SParams::PLAYERS]) : 0;
        $server[SParams::SERVER]['playersmax'] = $server[SParams::CONVARS]['maxclients'];
        $server[SParams::SERVER]['password']   = isset($server[SParams::CONVARS]['needpass']) && $server[SParams::CONVARS]['needpass'] > 0 && $server[SParams::CONVARS]['needpass'] < 4 ? 1 : 0;

        //---------------------------------------------------------+

        return true;
    }
}
