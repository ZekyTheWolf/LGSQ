<?php

namespace ZekyWolf\LGSQ\Protocols;

use ZekyWolf\LGSQ\Helpers\{
    Parse\Byte,
    Parse\Unpack,
    EServerParams as SParams,
    ERequestParams as RParams,
    EConnectionParams as CParams
};

class Query41
{
    public static function get(&$server, &$lgsl_need, &$lgsl_fp) // ONLY BEACON: World of Warcraft, Satisfactory
    {
        if (!$lgsl_fp) {
            return false;
        }

        $lgsl_need[RParams::CONVARS] = false;
        $lgsl_need[RParams::PLAYERS] = false;

        if ($server[SParams::BASIC][CParams::TYPE] == 'wow') {
            $buffer = fread($lgsl_fp, 5);
            if ($buffer && $buffer == "\x00\x2A\xEC\x01\x01") {
                $server[SParams::SERVER]['name']        = "World of Warcraft Server";
                $server[SParams::SERVER]['map']         = "Twisting Nether";
                return true;
            }
            return false;
        }
        if ($server[SParams::BASIC][CParams::TYPE] == 'sf') {
            fwrite($lgsl_fp, "\x00\x00\xd6\x9c\x28\x25\x00\x00\x00\x00");
            $buffer = fread($lgsl_fp, 128);
            if (!$buffer) {
                return false;
            }
            Byte::get($buffer, 11);
            $version = Unpack::get(Byte::get($buffer, 1), "H*");
            $version = Unpack::get(Byte::get($buffer, 1), "H*") . $version;
            $version = Unpack::get(Byte::get($buffer, 1), "H*") . $version;
            $server[SParams::SERVER]['name']        = "Satisfactory Dedicated Server";
            $server[SParams::SERVER]['map']         = "World";
            $server[SParams::CONVARS]['version']     = hexdec($version);
            return true;
        }
        return false;
    }
}
