<?php

namespace ZekyWolf\LGSQ\Protocols;

use ZekyWolf\LGSQ\Helpers\{
    Parse\Byte,
    Parse\Pascal,
    Parse\Unpack,
    EServerParams as SParams
};

class Query23
{
    public static function get(&$server, &$lgsl_need, &$lgsl_fp)
    {
        //---------------------------------------------------------+
        //  REFERENCE:
        //  http://siteinthe.us
        //  http://www.tribesmasterserver.com

        fwrite($lgsl_fp, "b++");

        $buffer = fread($lgsl_fp, 4096);

        if (!$buffer) {
            return false;
        }

        $buffer = substr($buffer, 4); // REMOVE HEADER

        //---------------------------------------------------------+

        $server[SParams::SERVER]['game']       = Pascal::get($buffer);
        $server[SParams::CONVARS]['version']    = Pascal::get($buffer);
        $server[SParams::SERVER]['name']       = Pascal::get($buffer);
        $server[SParams::CONVARS]['dedicated']  = ord(Byte::get($buffer, 1));
        $server[SParams::SERVER]['password']   = ord(Byte::get($buffer, 1));
        $server[SParams::SERVER]['players']    = ord(Byte::get($buffer, 1));
        $server[SParams::SERVER]['playersmax'] = ord(Byte::get($buffer, 1));
        $server[SParams::CONVARS]['cpu']        = Unpack::get(Byte::get($buffer, 2), "S");
        $server[SParams::CONVARS]['mod']        = Pascal::get($buffer);
        $server[SParams::CONVARS]['type']       = Pascal::get($buffer);
        $server[SParams::SERVER]['map']        = Pascal::get($buffer);
        $server[SParams::CONVARS]['motd']       = Pascal::get($buffer);
        $server[SParams::CONVARS]['teams']      = ord(Byte::get($buffer, 1));

        //---------------------------------------------------------+

        $team_field = "?".Pascal::get($buffer);
        $team_field = preg_split("\t", $team_field);

        foreach ($team_field as $key => $value) {
            $value = substr($value, 1);
            $value = strtolower($value);
            $team_field[$key] = $value;
        }

        //---------------------------------------------------------+

        $player_field = "?".Pascal::get($buffer);
        $player_field = preg_split("\t", $player_field);

        foreach ($player_field as $key => $value) {
            $value = substr($value, 1);
            $value = strtolower($value);

            if ($value == "player name") {
                $value = "name";
            }

            $player_field[$key] = $value;
        }

        $player_field[] = "unknown_1";
        $player_field[] = "unknown_2";

        //---------------------------------------------------------+

        for ($i=0; $i<$server[SParams::CONVARS]['teams']; $i++) {
            $team_name = Pascal::get($buffer);
            $team_info = Pascal::get($buffer);

            if (!$team_info) {
                continue;
            }

            $team_info = str_replace("%t", $team_name, $team_info);
            $team_info = preg_split("\t", $team_info);

            foreach ($team_info as $key => $value) {
                $field = $team_field[$key];
                $value = trim($value);

                if ($field == "team name") {
                    $field = "name";
                }

                $server[SParams::TEAMS][$i][$field] = $value;
            }
        }

        //---------------------------------------------------------+

        for ($i=0; $i<$server[SParams::SERVER]['players']; $i++) {
            $player_bits   = [];
            $player_bits[] = ord(Byte::get($buffer, 1)) * 4; // %p = PING
            $player_bits[] = ord(Byte::get($buffer, 1));     // %l = PACKET LOSS
            $player_bits[] = ord(Byte::get($buffer, 1));     // %t = TEAM
            $player_bits[] = Pascal::get($buffer);           // %n = PLAYER NAME
            $player_info   = Pascal::get($buffer);

            if (!$player_info) {
                continue;
            }

            $player_info = str_replace(array("%p","%l","%t","%n"), $player_bits, $player_info);
            $player_info = preg_split("\t", $player_info);

            foreach ($player_info as $key => $value) {
                $field = $player_field[$key];
                $value = trim($value);

                if ($field == "team") {
                    $value = $server[SParams::TEAMS][$value]['name'];
                }

                $server[SParams::PLAYERS][$i][$field] = $value;
            }
        }

        //---------------------------------------------------------+

        return true;
    }

}
