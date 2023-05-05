<?php

namespace ZekyWolf\LGSQ\Protocols;

use ZekyWolf\LGSQ\Helpers\{
    Parse\Byte,
    Parse\Pascal,
    Parse\Unpack,
    EServerParams as SParams,
    ERequestParams as RParams
};

class Query29
{
    public static function get(&$server, &$lgsl_need, &$lgsl_fp)
    {
        //---------------------------------------------------------+
        //  REFERENCE: http://www.cs2d.com/servers.php

        if ($lgsl_need[RParams::SERVER] || $lgsl_need[RParams::CONVARS]) {
            $lgsl_need[RParams::SERVER] = false;
            $lgsl_need[RParams::CONVARS] = false;

            fwrite($lgsl_fp, "\x01\x00\x03\x10\x21\xFB\x01\x75\x00");

            $buffer = fread($lgsl_fp, 4096);

            if (!$buffer) {
                return false;
            }

            $buffer = substr($buffer, 4); // REMOVE HEADER

            $server[SParams::CONVARS]['bit_flags']  = ord(Byte::get($buffer, 1)) - 48;
            $server[SParams::SERVER]['name']       = Pascal::get($buffer);
            $server[SParams::SERVER]['map']        = Pascal::get($buffer);
            $server[SParams::SERVER]['players']    = ord(Byte::get($buffer, 1));
            $server[SParams::SERVER]['playersmax'] = ord(Byte::get($buffer, 1));
            $server[SParams::CONVARS]['gamemode']   = ord(Byte::get($buffer, 1));
            $server[SParams::CONVARS]['bots']       = ord(Byte::get($buffer, 1));

            $server[SParams::SERVER]['password']        = ($server[SParams::CONVARS]['bit_flags'] & 1) ? "1" : "0";
            $server[SParams::CONVARS]['registered_only'] = ($server[SParams::CONVARS]['bit_flags'] & 2) ? "1" : "0";
            $server[SParams::CONVARS]['fog_of_war']      = ($server[SParams::CONVARS]['bit_flags'] & 4) ? "1" : "0";
            $server[SParams::CONVARS]['friendlyfire']    = ($server[SParams::CONVARS]['bit_flags'] & 8) ? "1" : "0";
        }

        if ($lgsl_need[SParams::PLAYERS]) {
            $lgsl_need[SParams::PLAYERS] = false;

            fwrite($lgsl_fp, "\x01\x00\xFB\x05");

            $buffer = fread($lgsl_fp, 4096);

            if (!$buffer) {
                return false;
            }

            $buffer = substr($buffer, 4); // REMOVE HEADER

            $player_total = ord(Byte::get($buffer, 1));

            for ($i=0; $i<$player_total; $i++) {
                $server[SParams::PLAYERS][$i]['pid']    = ord(Byte::get($buffer, 1));
                $server[SParams::PLAYERS][$i]['name']   = Pascal::get($buffer);
                $server[SParams::PLAYERS][$i]['team']   = ord(Byte::get($buffer, 1));
                $server[SParams::PLAYERS][$i]['score']  = Unpack::get(Byte::get($buffer, 4), "l");
                $server[SParams::PLAYERS][$i]['deaths'] = Unpack::get(Byte::get($buffer, 4), "l");
            }
        }

        //---------------------------------------------------------+

        return true;
    }
}
