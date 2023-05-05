<?php

namespace ZekyWolf\LGSQ\Protocols;

use ZekyWolf\LGSQ\Helpers\{
    Parse\Byte,
    Parse\Pascal,
    Parse\Unpack,
    Parse\ParseString,
    EServerParams as SParams
};

class Query25
{
    public static function get(&$server, &$lgsl_need, &$lgsl_fp)
    {
        //---------------------------------------------------------+
        //  REFERENCE: http://www.tribesnext.com

        fwrite($lgsl_fp, "\x12\x02\x21\x21\x21\x21");

        $buffer = fread($lgsl_fp, 4096);

        if (!$buffer) {
            return false;
        }

        $buffer = substr($buffer, 6); // REMOVE HEADER

        //---------------------------------------------------------+

        $server[SParams::SERVER]['game']       = Pascal::get($buffer);
        $server[SParams::CONVARS]['gamemode']   = Pascal::get($buffer);
        $server[SParams::SERVER]['map']        = Pascal::get($buffer);
        $server[SParams::CONVARS]['bit_flags']  = ord(Byte::get($buffer, 1));
        $server[SParams::SERVER]['players']    = ord(Byte::get($buffer, 1));
        $server[SParams::SERVER]['playersmax'] = ord(Byte::get($buffer, 1));
        $server[SParams::CONVARS]['bots']       = ord(Byte::get($buffer, 1));
        $server[SParams::CONVARS]['cpu']        = Unpack::get(Byte::get($buffer, 2), "S");
        $server[SParams::CONVARS]['motd']       = Pascal::get($buffer);
        $server[SParams::CONVARS]['unknown']    = Unpack::get(Byte::get($buffer, 2), "S");

        $server[SParams::CONVARS]['dedicated']  = ($server[SParams::CONVARS]['bit_flags'] & 1) ? "1" : "0";
        $server[SParams::SERVER]['password']   = ($server[SParams::CONVARS]['bit_flags'] & 2) ? "1" : "0";
        $server[SParams::CONVARS]['os']         = ($server[SParams::CONVARS]['bit_flags'] & 4) ? "L" : "W";
        $server[SParams::CONVARS]['tournament'] = ($server[SParams::CONVARS]['bit_flags'] & 8) ? "1" : "0";
        $server[SParams::CONVARS]['no_alias']   = ($server[SParams::CONVARS]['bit_flags'] & 16) ? "1" : "0";

        unset($server[SParams::CONVARS]['bit_flags']);

        //---------------------------------------------------------+

        $team_total = ParseString::get($buffer, 0, "\x0A");

        for ($i=0; $i<$team_total; $i++) {
            $server[SParams::TEAMS][$i]['name']  = ParseString::get($buffer, 0, "\x09");
            $server[SParams::TEAMS][$i]['score'] = ParseString::get($buffer, 0, "\x0A");
        }

        $player_total = ParseString::get($buffer, 0, "\x0A");

        for ($i=0; $i<$player_total; $i++) {
            Byte::get($buffer, 1); // ? 16
            Byte::get($buffer, 1); // ? 8 or 14 = BOT / 12 = ALIAS / 11 = NORMAL
            if (ord($buffer[0]) < 32) {
                Byte::get($buffer, 1);
            } // ? 8 PREFIXES SOME NAMES

            $server[SParams::PLAYERS][$i]['name']  = ParseString::get($buffer, 0, "\x11");
            ParseString::get($buffer, 0, "\x09"); // ALWAYS BLANK
            $server[SParams::PLAYERS][$i]['team']  = ParseString::get($buffer, 0, "\x09");
            $server[SParams::PLAYERS][$i]['score'] = ParseString::get($buffer, 0, "\x0A");
        }

        //---------------------------------------------------------+

        return true;
    }
}
