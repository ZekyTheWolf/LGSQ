<?php

namespace ZekyWolf\LGSQ\Protocols;

use ZekyWolf\LGSQ\Helpers\{
    Parse\Byte,
    Parse\Colors,
    Parse\Pascal,
    EServerParams as SParams
};

class Query08
{
    public static function get(&$server, &$lgsl_need, &$lgsl_fp)
    {
        //---------------------------------------------------------+

        fwrite($lgsl_fp, "s"); // ASE ( ALL SEEING EYE ) PROTOCOL

        $buffer = fread($lgsl_fp, 4096);

        if (!$buffer) {
            return false;
        }

        //---------------------------------------------------------+

        $buffer = substr($buffer, 4); // REMOVE HEADER

        $server[SParams::CONVARS]['gamename']   = Pascal::get($buffer, 1, -1);
        $server[SParams::CONVARS]['hostport']   = Pascal::get($buffer, 1, -1);
        $server[SParams::SERVER]['name']       = Colors::get(Pascal::get($buffer, 1, -1), $server[SParams::BASIC]['type']);
        $server[SParams::CONVARS]['gamemode']   = Pascal::get($buffer, 1, -1);
        $server[SParams::SERVER]['map']        = Pascal::get($buffer, 1, -1);
        $server[SParams::CONVARS]['version']    = Pascal::get($buffer, 1, -1);
        $server[SParams::SERVER]['password']   = Pascal::get($buffer, 1, -1);
        $server[SParams::SERVER]['players']    = Pascal::get($buffer, 1, -1);
        $server[SParams::SERVER]['playersmax'] = Pascal::get($buffer, 1, -1);

        while ($buffer && $buffer[0] != "\x01") {
            $item_key   = strtolower(Pascal::get($buffer, 1, -1));
            $item_value = Pascal::get($buffer, 1, -1);

            $server[SParams::CONVARS][$item_key] = $item_value;
        }

        $buffer = substr($buffer, 1); // REMOVE END MARKER

        //---------------------------------------------------------+

        $player_key = 0;

        while ($buffer) {
            $bit_flags = Byte::get($buffer, 1);
            // FIELDS HARD CODED BELOW BECAUSE GAMES DO NOT USE THEM PROPERLY

            if($bit_flags == "\x3D") {
                $field_list = [ "name", "score", "", "time" ];
            } // FARCRY PLAYERS CONNECTING
            elseif ($server[SParams::BASIC]['type'] == "farcry") {
                $field_list = [ "name", "team", "", "score", "ping", "time" ];
            } // FARCRY PLAYERS JOINED
            elseif ($server[SParams::BASIC]['type'] == "mta") {
                $field_list = [ "name", "", "", "score", "ping", "" ];
            } elseif ($server[SParams::BASIC]['type'] == "painkiller") {
                $field_list = [ "name", "", "skin",  "score", "ping", "" ];
            } elseif ($server[SParams::BASIC]['type'] == "soldat") {
                $field_list = [ "name", "team", "", "score", "ping", "time" ];
            }

            foreach ($field_list as $item_key) {
                $item_value = Pascal::get($buffer, 1, -1);

                if (!$item_key) {
                    continue;
                }

                if ($item_key == "name") {
                    Colors::get($item_value, $server[SParams::BASIC]['type']);
                }

                $server[SParams::PLAYERS][$player_key][$item_key] = $item_value;
            }

            $player_key ++;
        }

        //---------------------------------------------------------+

        return true;
    }

}
