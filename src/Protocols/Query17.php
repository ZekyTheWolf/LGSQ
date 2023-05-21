<?php

namespace ZekyWolf\LGSQ\Protocols;

use ZekyWolf\LGSQ\Helpers\{
    Parse\ParseString,
    Parse\Colors,
    EServerParams as SParams,
    EConnectionParams as CParams
};

class Query17
{
    public static function get(&$server, &$lgsl_need, &$lgsl_fp)
    {
        //---------------------------------------------------------+
        //  REFERENCE: http://masterserver.savage.s2games.com

        fwrite($lgsl_fp, "\x9e\x4c\x23\x00\x00\xce\x21\x21\x21\x21");

        $buffer = fread($lgsl_fp, 4096);

        if (!$buffer) {
            return false;
        }

        //---------------------------------------------------------+

        $buffer = substr($buffer, 12); // REMOVE HEADER

        while ($key = strtolower(ParseString::get($buffer, 0, "\xFE"))) {
            if ($key == "players") {
                break;
            }

            $value = ParseString::get($buffer, 0, "\xFF");
            $value = str_replace("\x00", "", $value);
            $value = ParseString::get($value, $server[SParams::BASIC][CParams::TYPE]);

            $server[SParams::CONVARS][$key] = $value;
        }

        $server[SParams::SERVER]['name']       = $server[SParams::CONVARS]['name'];
        unset($server[SParams::CONVARS]['name']);
        $server[SParams::SERVER]['map']        = $server[SParams::CONVARS]['world'];
        unset($server[SParams::CONVARS]['world']);
        $server[SParams::SERVER]['players']    = $server[SParams::CONVARS]['cnum'];
        unset($server[SParams::CONVARS]['cnum']);
        $server[SParams::SERVER]['playersmax'] = $server[SParams::CONVARS]['cmax'];
        unset($server[SParams::CONVARS]['cnum']);
        $server[SParams::SERVER]['password']   = $server[SParams::CONVARS]['pass'];
        unset($server[SParams::CONVARS]['cnum']);

        //---------------------------------------------------------+

        $server[SParams::TEAMS][0]['name'] = $server[SParams::CONVARS]['race1'];
        $server[SParams::TEAMS][1]['name'] = $server[SParams::CONVARS]['race2'];
        $server[SParams::TEAMS][2]['name'] = "spectator";

        $team_key   = -1;
        $player_key = 0;

        while ($value = ParseString::get($buffer, 0, "\x0a")) {
            if ($value[0] == "\x00") {
                break;
            }
            if ($value[0] != "\x20") {
                $team_key++;
                continue;
            }

            $server[SParams::PLAYERS][$player_key]['name'] = Colors::get(substr($value, 1), $server[SParams::BASIC][CParams::TYPE]);
            $server[SParams::PLAYERS][$player_key]['team'] = $server[SParams::TEAMS][$team_key]['name'];

            $player_key++;
        }

        //---------------------------------------------------------+

        return true;
    }
}
