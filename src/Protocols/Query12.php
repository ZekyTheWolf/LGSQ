<?php

namespace ZekyWolf\LGSQ\Protocols;

use ZekyWolf\LGSQ\Helpers\{
    Parse\Byte,
    Parse\Pascal,
    Parse\Unpack,
    EServerParams as SParams,
    ERequestParams as RParams,
    EConnectionParams as CParams
};

class Query12
{
    public static function get(&$server, &$lgsl_need, &$lgsl_fp)
    {
        //---------------------------------------------------------+

        if ($server[SParams::BASIC][CParams::TYPE] == "samp") {
            $challenge_packet = "SAMP\x21\x21\x21\x21\x00\x00";
        } elseif ($server[SParams::BASIC][CParams::TYPE] == "vcmp") {
            $challenge_packet = "VCMP\x21\x21\x21\x21\x00\x00";
            $lgsl_need[RParams::CONVARS] = false;
        }

        if($lgsl_need[RParams::SERVER]) {
            $challenge_packet .= "i";
        } elseif ($lgsl_need[RParams::CONVARS]) {
            $challenge_packet .= "r";
        } elseif ($lgsl_need[RParams::PLAYERS] && $server[SParams::BASIC][CParams::TYPE] == "samp") {
            $challenge_packet .= "d";
        } elseif ($lgsl_need[RParams::PLAYERS] && $server[SParams::BASIC][CParams::TYPE] == "vcmp") {
            $challenge_packet .= "c";
        }

        fwrite($lgsl_fp, $challenge_packet);

        $buffer = fread($lgsl_fp, 4096);

        if (strlen($buffer) == 0) { // IN CASE OF PACKET LOSS
            fwrite($lgsl_fp, $challenge_packet);
            $buffer = fread($lgsl_fp, 4096);
        }

        if (!$buffer && substr($challenge_packet, 10, 1) == "i") {
            return false;
        }

        //---------------------------------------------------------+

        $buffer = substr($buffer, 10); // REMOVE HEADER

        $response_type = Byte::get($buffer, 1);

        //---------------------------------------------------------+

        if ($response_type == "i") {
            $lgsl_need[RParams::SERVER] = false;

            if ($server[SParams::BASIC][CParams::TYPE] == "vcmp") {
                $buffer = substr($buffer, 12);
            }

            $server[SParams::SERVER]['password']   = ord(Byte::get($buffer, 1));
            $server[SParams::SERVER]['players']    = Unpack::get(Byte::get($buffer, 2), "S");
            $server[SParams::SERVER]['playersmax'] = Unpack::get(Byte::get($buffer, 2), "S");
            $server[SParams::SERVER]['name']       = Pascal::get($buffer, 4);
            $server[SParams::CONVARS]['gamemode']   = Pascal::get($buffer, 4);
            $server[SParams::SERVER]['map']        = Pascal::get($buffer, 4);
        }

        //---------------------------------------------------------+

        elseif ($response_type == "r") {
            $lgsl_need[RParams::CONVARS] = false;

            $item_total = Unpack::get(Byte::get($buffer, 2), "S");

            for ($i=0; $i<$item_total; $i++) {
                if (!$buffer) {
                    return false;
                }

                $data_key   = strtolower(Pascal::get($buffer));
                $data_value = Pascal::get($buffer);

                $server[SParams::CONVARS][$data_key] = $data_value;
            }
        }

        //---------------------------------------------------------+

        elseif ($response_type == "d") {
            $lgsl_need[RParams::PLAYERS] = false;

            $player_total = Unpack::get(Byte::get($buffer, 2), "S");

            for ($i=0; $i<$player_total; $i++) {
                if (!$buffer) {
                    return false;
                }

                $server[SParams::PLAYERS][$i]['pid']   = ord(Byte::get($buffer, 1));
                $server[SParams::PLAYERS][$i]['name']  = Pascal::get($buffer);
                $server[SParams::PLAYERS][$i]['score'] = Unpack::get(Byte::get($buffer, 4), "S");
                $server[SParams::PLAYERS][$i]['ping']  = Unpack::get(Byte::get($buffer, 4), "S");
            }
        }

        //---------------------------------------------------------+

        elseif ($response_type == "c") {
            $lgsl_need[RParams::PLAYERS] = false;

            $player_total = Unpack::get(Byte::get($buffer, 2), "S");

            for ($i=0; $i<$player_total; $i++) {
                if (!$buffer) {
                    return false;
                }

                $server[SParams::PLAYERS][$i]['name']  = Pascal::get($buffer);
            }
        }

        //---------------------------------------------------------+

        return true;
    }
}
