<?php

namespace ZekyWolf\LGSQ\Protocols;

use ZekyWolf\LGSQ\Helpers\{
    Parse\Byte,
    Parse\Unpack,
    Parse\ParseString,
    Parse\Time,
    ERequestParams as RParams
};

class Query20
{
    public static function get(&$server, &$lgsl_need, &$lgsl_fp)
    {
        //---------------------------------------------------------+

        if ($lgsl_need[RParams::SERVER]) {
            fwrite($lgsl_fp, "\xFF\xFF\xFF\xFFFLSQ");
        } else {
            fwrite($lgsl_fp, "\xFF\xFF\xFF\xFF\x57");

            $challenge_packet = fread($lgsl_fp, 4096);

            if (!$challenge_packet) {
                return false;
            }

            $challenge_code = substr($challenge_packet, 5, 4);

            if($lgsl_need[RParams::CONVARS]) {
                fwrite($lgsl_fp, "\xFF\xFF\xFF\xFF\x56{$challenge_code}");
            } elseif ($lgsl_need[RParams::PLAYERS]) {
                fwrite($lgsl_fp, "\xFF\xFF\xFF\xFF\x55{$challenge_code}");
            }
        }

        $buffer = fread($lgsl_fp, 4096);
        $buffer = substr($buffer, 4); // REMOVE HEADER

        if (!$buffer) {
            return false;
        }

        //---------------------------------------------------------+

        $response_type = Byte::get($buffer, 1);

        if ($response_type == "I") {
            $server[RParams::CONVARS]['netcode']     = ord(Byte::get($buffer, 1));
            $server[RParams::SERVER]['name']        = ParseString::get($buffer);
            $server[RParams::SERVER]['map']         = ParseString::get($buffer);
            $server[RParams::SERVER]['game']        = ParseString::get($buffer);
            $server[RParams::CONVARS]['gamemode']    = ParseString::get($buffer);
            $server[RParams::CONVARS]['description'] = ParseString::get($buffer);
            $server[RParams::CONVARS]['version']     = ParseString::get($buffer);
            $server[RParams::CONVARS]['hostport']    = Unpack::get(Byte::get($buffer, 2), "n");
            $server[RParams::SERVER]['players']     = Unpack::get(Byte::get($buffer, 1), "C");
            $server[RParams::SERVER]['playersmax']  = Unpack::get(Byte::get($buffer, 1), "C");
            $server[RParams::CONVARS]['dedicated']   = Byte::get($buffer, 1);
            $server[RParams::CONVARS]['os']          = Byte::get($buffer, 1);
            $server[RParams::SERVER]['password']    = Unpack::get(Byte::get($buffer, 1), "C");
            $server[RParams::CONVARS]['anticheat']   = Unpack::get(Byte::get($buffer, 1), "C");
            $server[RParams::CONVARS]['cpu_load']    = round(3.03 * Unpack::get(Byte::get($buffer, 1), "C"))."%";
            $server[RParams::CONVARS]['round']       = Unpack::get(Byte::get($buffer, 1), "C");
            $server[RParams::CONVARS]['roundsmax']   = Unpack::get(Byte::get($buffer, 1), "C");
            $server[RParams::CONVARS]['timeleft']    = Time::get(Unpack::get(Byte::get($buffer, 2), "S") / 250);
        } elseif ($response_type == "E") {
            $returned = Unpack::get(Byte::get($buffer, 2), "S");

            while ($buffer) {
                $item_key   = strtolower(ParseString::get($buffer));
                $item_value = ParseString::get($buffer);

                $server[RParams::CONVARS][$item_key] = $item_value;
            }
        } elseif ($response_type == "D") {
            $returned = ord(Byte::get($buffer, 1));

            $player_key = 0;

            while ($buffer) {
                $server[RParams::PLAYERS][$player_key]['pid']   = ord(Byte::get($buffer, 1));
                $server[RParams::PLAYERS][$player_key]['name']  = ParseString::get($buffer);
                $server[RParams::PLAYERS][$player_key]['score'] = Unpack::get(Byte::get($buffer, 4), "N");
                $server[RParams::PLAYERS][$player_key]['time']  = Time::get(Unpack::get(strrev(Byte::get($buffer, 4)), "f"));
                $server[RParams::PLAYERS][$player_key]['ping']  = Unpack::get(Byte::get($buffer, 2), "n");
                $server[RParams::PLAYERS][$player_key]['uid']   = Unpack::get(Byte::get($buffer, 4), "N");
                $server[RParams::PLAYERS][$player_key]['team']  = ord(Byte::get($buffer, 1));

                $player_key ++;
            }
        }

        //---------------------------------------------------------+

        if($lgsl_need[RParams::SERVER]) {
            $lgsl_need[RParams::SERVER] = false;
        }
        if($lgsl_need[RParams::CONVARS]) {
            $lgsl_need[RParams::CONVARS] = false;
        }
        if($lgsl_need[RParams::PLAYERS]) {
            $lgsl_need[RParams::PLAYERS] = false;
        }

        //---------------------------------------------------------+

        return true;
    }

}
