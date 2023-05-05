<?php

namespace ZekyWolf\LGSQ\Protocols;

use ZekyWolf\LGSQ\Helpers\{
    Parse\ParseString,
    EServerParams as SParams
};

class Query21
{
    public static function get(&$server, &$lgsl_need, &$lgsl_fp)
    {
        //---------------------------------------------------------+

        fwrite($lgsl_fp, "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xffgief");

        $buffer = fread($lgsl_fp, 4096);
        $buffer = substr($buffer, 20); // REMOVE HEADER

        if (!$buffer) {
            return false;
        }

        //---------------------------------------------------------+

        $server[SParams::SERVER]['name']       = ParseString::get($buffer);
        $server[SParams::SERVER]['map']        = ParseString::get($buffer);
        $server[SParams::CONVARS]['gamemode']   = ParseString::get($buffer);
        $server[SParams::SERVER]['password']   = ParseString::get($buffer);
        $server[SParams::CONVARS]['progress']   = ParseString::get($buffer)."%";
        $server[SParams::SERVER]['players']    = ParseString::get($buffer);
        $server[SParams::SERVER]['playersmax'] = ParseString::get($buffer);

        switch ($server[SParams::CONVARS]['gamemode']) {
            case 0: {
                $server[SParams::CONVARS]['gamemode'] = "Deathmatch";
                break;
            }
            case 1: {
                $server[SParams::CONVARS]['gamemode'] = "Team Deathmatch";
                break;
            }
            case 2: {
                $server[SParams::CONVARS]['gamemode'] = "Capture The Flag";
                break;
            }
        }

        //---------------------------------------------------------+

        $player_key = 0;

        while ($buffer) {
            $server[SParams::PLAYERS][$player_key]['name']  = ParseString::get($buffer);
            $server[SParams::PLAYERS][$player_key]['score'] = ParseString::get($buffer);

            $player_key ++;
        }

        //---------------------------------------------------------+

        return true;
    }

}
