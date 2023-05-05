<?php

namespace ZekyWolf\LGSQ\Protocols;

use ZekyWolf\LGSQ\Helpers\{
    Parse\Byte,
    Parse\Unpack,
    Parse\ParseString,
    EServerParams as SParams,
};

class Query13
{
    public static function get(&$server, &$lgsl_need, &$lgsl_fp)
    {
        //---------------------------------------------------------+

        $buffer_s = "";
        fwrite($lgsl_fp, "\x21\x21\x21\x21\x00"); // REQUEST [s]
        $buffer_e = "";
        fwrite($lgsl_fp, "\x21\x21\x21\x21\x01"); // REQUEST [e]
        $buffer_p = "";
        fwrite($lgsl_fp, "\x21\x21\x21\x21\x02"); // REQUEST [p]

        //---------------------------------------------------------+

        while ($packet = fread($lgsl_fp, 4096)) {
            if     ($packet[4] == "\x00") {
                $buffer_s .= substr($packet, 5);
            } elseif ($packet[4] == "\x01") {
                $buffer_e .= substr($packet, 5);
            } elseif ($packet[4] == "\x02") {
                $buffer_p .= substr($packet, 5);
            }
        }

        if (!$buffer_s) {
            return false;
        }

        //---------------------------------------------------------+
        //  SOME VALUES START WITH A PASCAL LENGTH AND END WITH A NULL BUT THERE IS AN ISSUE WHERE
        //  CERTAIN CHARACTERS CAUSE A WRONG PASCAL LENGTH AND NULLS TO APPEAR WITHIN NAMES

        $buffer_s = str_replace("\xa0", "\x20", $buffer_s); // REPLACE SPECIAL SPACE WITH NORMAL SPACE
        $buffer_s = substr($buffer_s, 5);
        $server[SParams::CONVARS]['hostport']   = Unpack::get(Byte::get($buffer_s, 4), "S");
        $buffer_s = substr($buffer_s, 4);
        $server[SParams::SERVER]['name']       = ParseString::get($buffer_s, 1);
        $server[SParams::SERVER]['map']        = ParseString::get($buffer_s, 1);
        $server[SParams::CONVARS]['gamemode']   = ParseString::get($buffer_s, 1);
        $server[SParams::SERVER]['players']    = Unpack::get(Byte::get($buffer_s, 4), "S");
        $server[SParams::SERVER]['playersmax'] = Unpack::get(Byte::get($buffer_s, 4), "S");

        //---------------------------------------------------------+

        while ($buffer_e && $buffer_e[0] != "\x00") {
            $item_key   = strtolower(ParseString::get($buffer_e, 1));
            $item_value = ParseString::get($buffer_e, 1);

            $item_key   = str_replace("\x1B\xFF\xFF\x01", "", $item_key);   // REMOVE MOD
            $item_value = str_replace("\x1B\xFF\xFF\x01", "", $item_value); // GARBAGE

            $server[SParams::CONVARS][$item_key] = $item_value;
        }

        //---------------------------------------------------------+
        //  THIS PROTOCOL RETURNS MORE INFO THAN THE ALTERNATIVE BUT IT DOES NOT
        //  RETURN THE GAME NAME ! SO WE HAVE MANUALLY DETECT IT USING THE GAME TYPE

        $tmp = strtolower(substr($server[SParams::CONVARS]['gamemode'], 0, 2));

        if ($tmp == "ro") {
            $server[SParams::SERVER]['game'] = "Red Orchestra";
        } elseif ($tmp == "kf") {
            $server[SParams::SERVER]['game'] = "Killing Floor";
        }

        $server[SParams::SERVER]['password'] = empty($server[SParams::CONVARS]['password']) && empty($server[SParams::CONVARS]['gamepassword']) ? "0" : "1";

        //---------------------------------------------------------+

        $player_key = 0;

        while ($buffer_p && $buffer_p[0] != "\x00") {
            $server[SParams::PLAYERS][$player_key]['pid']   = Unpack::get(Byte::get($buffer_p, 4), "S");

            $end_marker = ord($buffer_p[0]) > 64 ? "\x00\x00" : "\x00"; // DIRTY WORK-AROUND FOR NAMES WITH PROBLEM CHARACTERS

            $server[SParams::PLAYERS][$player_key]['name']  = ParseString::get($buffer_p, 1, $end_marker);
            $server[SParams::PLAYERS][$player_key]['ping']  = Unpack::get(Byte::get($buffer_p, 4), "S");
            $server[SParams::PLAYERS][$player_key]['score'] = Unpack::get(Byte::get($buffer_p, 4), "s");
            $tmp                               = Byte::get($buffer_p, 4);

            if ($tmp[3] == "\x20") {
                $server[SParams::PLAYERS][$player_key]['team'] = 1;
            } elseif ($tmp[3] == "\x40") {
                $server[SParams::PLAYERS][$player_key]['team'] = 2;
            }

            $player_key ++;
        }

        //---------------------------------------------------------+

        return true;
    }
}
