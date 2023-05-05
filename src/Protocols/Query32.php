<?php

namespace ZekyWolf\LGSQ\Protocols;

use ZekyWolf\LGSQ\Helpers\{
    Parse\Byte,
    Parse\Pascal,
    EServerParams as SParams
};

class Query32
{
    public static function get(&$server, &$lgsl_need, &$lgsl_fp)
    {
        //---------------------------------------------------------+

        fwrite($lgsl_fp, "\x05\x00\x00\x01\x0A");

        $buffer = fread($lgsl_fp, 4096);

        if (!$buffer) {
            return false;
        }

        $buffer = substr($buffer, 5); // REMOVE HEADER

        $server[SParams::SERVER]['name']       = Pascal::get($buffer);
        $server[SParams::SERVER]['map']        = Pascal::get($buffer);
        $server[SParams::SERVER]['players']    = ord(Byte::get($buffer, 1));
        $server[SParams::SERVER]['playersmax'] = 0; // HELD ON MASTER

        // DOES NOT RETURN PLAYER INFORMATION

        //---------------------------------------------------------+

        return true;
    }
}
