<?php

namespace ZekyWolf\LGSQ\Protocols;

use ZekyWolf\LGSQ\Helpers\{
    Parse\Byte,
    Parse\Unpack,
    EServerParams as SParams,
};

class Query43
{
    public static function get(&$server, &$lgsl_need, &$lgsl_fp) // Mumble
    {
        if (!$lgsl_fp) {
            return false;
        }
        fwrite($lgsl_fp, "\x00\x00\x00\x00\x01\x02\x03\x04\x05\x06\x07\x08");
        $buffer = fread($lgsl_fp, 4096);
        if (!$buffer) {
            return false;
        }
        $server[SParams::SERVER]['name']        = "Mumble Server";
        $server[SParams::SERVER]['map']         = "Mumble";
        Byte::get($buffer, 1); // 0
        $server[SParams::CONVARS]['version'] = ord(Byte::get($buffer, 1)) .".". ord(Byte::get($buffer, 1)) .".". ord(Byte::get($buffer, 1));
        Byte::get($buffer, 8); // challenge
        $server[SParams::SERVER]['players'] = Unpack::get(Byte::get($buffer, 4), "N");
        $server[SParams::SERVER]['playersmax'] = Unpack::get(Byte::get($buffer, 4), "N");
        return true;
    }
}
