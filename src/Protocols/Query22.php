<?php

namespace ZekyWolf\LGSQ\Protocols;

use ZekyWolf\LGSQ\Helpers\{
    Parse\Byte,
    Parse\Unpack,
    Parse\ParseString,
    EServerParams as SParams
};

class Query22
{
    public static function get(&$server, &$lgsl_need, &$lgsl_fp)
    {
        //---------------------------------------------------------+

        fwrite($lgsl_fp, "\x03\x00\x00");

        $buffer = fread($lgsl_fp, 4096);
        $buffer = substr($buffer, 3); // REMOVE HEADER

        if (!$buffer) {
            return false;
        }

        $response_type = ord(Byte::get($buffer, 1)); // TYPE SHOULD BE 4

        //---------------------------------------------------------+

        $grf_count = ord(Byte::get($buffer, 1));

        for ($a=0; $a<$grf_count; $a++) {
            $server[SParams::CONVARS]['grf_'.$a.'_id'] = strtoupper(dechex(Unpack::get(Byte::get($buffer, 4), "N")));

            for ($b=0; $b<16; $b++) {
                $server[SParams::CONVARS]['grf_'.$a.'_md5'] .= strtoupper(dechex(ord(Byte::get($buffer, 1))));
            }
        }

        //---------------------------------------------------------+

        $server[SParams::CONVARS]['date_current']   = Unpack::get(Byte::get($buffer, 4), "L");
        $server[SParams::CONVARS]['date_start']     = Unpack::get(Byte::get($buffer, 4), "L");
        $server[SParams::CONVARS]['companies_max']  = ord(Byte::get($buffer, 1));
        $server[SParams::CONVARS]['companies']      = ord(Byte::get($buffer, 1));
        $server[SParams::CONVARS]['spectators_max'] = ord(Byte::get($buffer, 1));
        $server[SParams::SERVER]['name']           = ParseString::get($buffer);
        $server[SParams::CONVARS]['version']        = ParseString::get($buffer);
        $server[SParams::CONVARS]['language']       = ord(Byte::get($buffer, 1));
        $server[SParams::SERVER]['password']       = ord(Byte::get($buffer, 1));
        $server[SParams::SERVER]['playersmax']     = ord(Byte::get($buffer, 1));
        $server[SParams::SERVER]['players']        = ord(Byte::get($buffer, 1));
        $server[SParams::CONVARS]['spectators']     = ord(Byte::get($buffer, 1));
        $server[SParams::SERVER]['map']            = ParseString::get($buffer);
        $server[SParams::CONVARS]['map_width']      = Unpack::get(Byte::get($buffer, 2), "S");
        $server[SParams::CONVARS]['map_height']     = Unpack::get(Byte::get($buffer, 2), "S");
        $server[SParams::CONVARS]['map_set']        = ord(Byte::get($buffer, 1));
        $server[SParams::CONVARS]['dedicated']      = ord(Byte::get($buffer, 1));

        // DOES NOT RETURN PLAYER INFORMATION

        //---------------------------------------------------------+

        return true;
    }
}
