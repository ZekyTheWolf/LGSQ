<?php

namespace ZekyWolf\LGSQ\Protocols;

use ZekyWolf\LGSQ\Helpers\{
    Parse\Byte,
    Parse\Unpack,
    Parse\Colors,
    EServerParams as SParams,
    ERequestParams as RParams
};

class Query42
{
    public static function get(&$server, &$lgsl_need, &$lgsl_fp) // Factorio
    {
        if (!$lgsl_fp) {
            return false;
        }

        $lgsl_need[RParams::CONVARS] = false;
        $lgsl_need[RParams::PLAYERS] = false;

        fwrite($lgsl_fp, "\x30");
        $packet = fread($lgsl_fp, 4096);
        if (!$packet) {
            return false;
        }
        $buffer = $packet;
        while (strlen($packet) >= 504) {
            $packet = fread($lgsl_fp, 512);
            Byte::get($packet, 4);
            $buffer .= $packet;
        }
        if (strlen($buffer) > 508) {
            Byte::get($buffer, 3);
        }
        Byte::get($buffer, 13);
        $server[SParams::SERVER]['name']        = Colors::get(Byte::get($buffer, ord(Byte::get($buffer, 1))), "factorio");
        $server[SParams::CONVARS]['version']     = ord(Byte::get($buffer, 1)) . "." . ord(Byte::get($buffer, 1)) . "." . ord(Byte::get($buffer, 1));
        $server[SParams::CONVARS]['build']       = Unpack::get(Byte::get($buffer, 2), "S");
        $desc = ord(Byte::get($buffer, 1));
        $desc = $desc === 255 ? Unpack::get(Byte::get($buffer, 2), "S")+2 : $desc;
        $server[SParams::CONVARS]['description'] = Colors::get(Byte::get($buffer, $desc), "factorio");
        $maxplayers = Unpack::get(Byte::get($buffer, 2), "S");
        $server[SParams::SERVER]['playersmax']  = $maxplayers ? $maxplayers : 9999;
        $server[SParams::CONVARS]['time']        = Unpack::get(Byte::get($buffer, 2), "S") . "m";
        Byte::get($buffer, 2);
        $server[SParams::SERVER]['password']    = ord(Byte::get($buffer, 1));
        Byte::get($buffer, 1);
        Byte::get($buffer, ord(Byte::get($buffer, 1)));
        $server[SParams::CONVARS]['public']      = ord(Byte::get($buffer, 1)) ? "true" : "false";
        Byte::get($buffer, 1);
        $server[SParams::CONVARS]['lan']         = ord(Byte::get($buffer, 1)) ? "true" : "false";
        $server[SParams::CONVARS]['mods']        = "";
        $gamemodes = ord(Byte::get($buffer, 1));
        for ($i = 0; $i < $gamemodes; $i++) {
            $server[SParams::CONVARS]['mods']      .= Byte::get($buffer, ord(Byte::get($buffer, 1))) . " " . "(" . ord(Byte::get($buffer, 1)) . "." . ord(Byte::get($buffer, 1)) . "." . ord(Byte::get($buffer, 1)) . ")\n";
            Byte::get($buffer, 4);
        }
        $server[SParams::CONVARS]['tags']        = "";
        $tags = ord(Byte::get($buffer, 1));
        for ($i = 0; $i < $tags; $i++) {
            $tag = ord(Byte::get($buffer, 1));
            $tag = $tag === 255 ? Unpack::get(Byte::get($buffer, 2), "S")+2 : $tag;
            $server[SParams::CONVARS]['tags'] .= Colors::get(Byte::get($buffer, $tag), "factorio") . "\n";
        }
        $players = ord(Byte::get($buffer, 1));
        for ($i = 0; $i < $players; $i++) {
            $server[SParams::PLAYERS][$i]['name']  = Byte::get($buffer, ord(Byte::get($buffer, 1)));
        }
        $server[SParams::SERVER]['players']     = count($server[SParams::PLAYERS]);
        $server[SParams::SERVER]['map']         = "World";
        return true;
    }
}
