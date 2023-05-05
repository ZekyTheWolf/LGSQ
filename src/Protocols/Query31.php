<?php

namespace ZekyWolf\LGSQ\Protocols;

use ZekyWolf\LGSQ\Helpers\{
    Parse\Byte,
    Parse\Unpack,
    Parse\ParseString,
    EServerParams as SParams
};

class Query31
{
    public static function get(&$server, &$lgsl_need, &$lgsl_fp)
    {
        //---------------------------------------------------------+
        //  AVP 2010 ONLY ROUGHLY FOLLOWS THE SOURCE QUERY FORMAT
        //  SERVER RULES ARE ON THE END OF THE INFO RESPONSE

        fwrite($lgsl_fp, "\xFF\xFF\xFF\xFF\x54Source Engine Query\x00");

        $buffer = fread($lgsl_fp, 4096);

        if (!$buffer) {
            return false;
        }

        $buffer = substr($buffer, 5); // REMOVE HEADER

        $server[SParams::CONVARS]['netcode']     = ord(Byte::get($buffer, 1));
        $server[SParams::SERVER]['name']        = ParseString::get($buffer);
        $server[SParams::SERVER]['map']         = ParseString::get($buffer);
        $server[SParams::SERVER]['game']        = ParseString::get($buffer);
        $server[SParams::CONVARS]['description'] = ParseString::get($buffer);
        $server[SParams::CONVARS]['appid']       = Unpack::get(Byte::get($buffer, 2), "S");
        $server[SParams::SERVER]['players']     = ord(Byte::get($buffer, 1));
        $server[SParams::SERVER]['playersmax']  = ord(Byte::get($buffer, 1));
        $server[SParams::CONVARS]['bots']        = ord(Byte::get($buffer, 1));
        $server[SParams::CONVARS]['dedicated']   = Byte::get($buffer, 1);
        $server[SParams::CONVARS]['os']          = Byte::get($buffer, 1);
        $server[SParams::SERVER]['password']    = ord(Byte::get($buffer, 1));
        $server[SParams::CONVARS]['anticheat']   = ord(Byte::get($buffer, 1));
        $server[SParams::CONVARS]['version']     = ParseString::get($buffer);

        $buffer = substr($buffer, 1);
        $server[SParams::CONVARS]['hostport']     = Unpack::get(Byte::get($buffer, 2), "S");
        $server[SParams::CONVARS]['friendlyfire'] = $buffer[124];

        // DOES NOT RETURN PLAYER INFORMATION

        //---------------------------------------------------------+

        return true;
    }
}
