<?php

namespace ZekyWolf\LGSQ\Protocols;

use ZekyWolf\LGSQ\Helpers\{
    Parse\Byte,
    Parse\ParseString,
    EServerParams as SParams
};

class Query18
{
    public static function get(&$server, &$lgsl_need, &$lgsl_fp)
    {
        //---------------------------------------------------------+
        //  REFERENCE: http://masterserver.savage2.s2games.com

        fwrite($lgsl_fp, "\x01");

        $buffer = fread($lgsl_fp, 4096);

        if (!$buffer) {
            return false;
        }

        //---------------------------------------------------------+

        $buffer = substr($buffer, 12); // REMOVE HEADER

        $server[SParams::SERVER]['name']            = ParseString::get($buffer);
        $server[SParams::SERVER]['players']         = ord(Byte::get($buffer, 1));
        $server[SParams::SERVER]['playersmax']      = ord(Byte::get($buffer, 1));
        $server[SParams::CONVARS]['time']            = ParseString::get($buffer);
        $server[SParams::SERVER]['map']             = ParseString::get($buffer);
        $server[SParams::CONVARS]['nextmap']         = ParseString::get($buffer);
        $server[SParams::CONVARS]['location']        = ParseString::get($buffer);
        $server[SParams::CONVARS]['minimum_players'] = ord(ParseString::get($buffer));
        $server[SParams::CONVARS]['gamemode']        = ParseString::get($buffer);
        $server[SParams::CONVARS]['version']         = ParseString::get($buffer);
        $server[SParams::CONVARS]['minimum_level']   = ord(Byte::get($buffer, 1));

        // DOES NOT RETURN PLAYER INFORMATION

        //---------------------------------------------------------+

        return true;
    }

}
