<?php

namespace ZekyWolf\LGSQ\Protocols;

use ZekyWolf\LGSQ\Helpers\{
    Parse\Byte,
    Parse\Pascal,
    Parse\Unpack,
    Parse\ParseString,
    Parse\Time,
    EServerParams as SParams
};

class Query19
{
    public static function get(&$server, &$lgsl_need, &$lgsl_fp)
    {
        //---------------------------------------------------------+

        fwrite($lgsl_fp, "\xC0\xDE\xF1\x11\x42\x06\x00\xF5\x03\x21\x21\x21\x21");

        $buffer = fread($lgsl_fp, 4096);

        if (!$buffer) {
            return false;
        }

        //---------------------------------------------------------+

        $buffer = substr($buffer, 25); // REMOVE HEADER

        $server[SParams::SERVER]['name']       = ParseString::get(Pascal::get($buffer, 4, 3, -3));
        $server[SParams::SERVER]['map']        = ParseString::get(Pascal::get($buffer, 4, 3, -3));
        $server[SParams::CONVARS]['nextmap']    = ParseString::get(Pascal::get($buffer, 4, 3, -3));
        $server[SParams::CONVARS]['gametype']   = ParseString::get(Pascal::get($buffer, 4, 3, -3));

        $buffer = substr($buffer, 1);

        $server[SParams::SERVER]['password']   = ord(Byte::get($buffer, 1));
        $server[SParams::SERVER]['playersmax'] = ord(Byte::get($buffer, 4));
        $server[SParams::SERVER]['players']    = ord(Byte::get($buffer, 4));

        //---------------------------------------------------------+

        for ($player_key=0; $player_key<$server[SParams::SERVER]['players']; $player_key++) {
            $server[SParams::PLAYERS][$player_key]['name'] = ParseString::get(Pascal::get($buffer, 4, 3, -3));
        }

        //---------------------------------------------------------+

        $buffer = substr($buffer, 17);

        $server[SParams::CONVARS]['version']    = ParseString::get(Pascal::get($buffer, 4, 3, -3));
        $server[SParams::CONVARS]['mods']       = ParseString::get(Pascal::get($buffer, 4, 3, -3));
        $server[SParams::CONVARS]['dedicated']  = ord(Byte::get($buffer, 1));
        $server[SParams::CONVARS]['time']       = Time::get(Unpack::get(Byte::get($buffer, 4), "f"));
        $server[SParams::CONVARS]['status']     = ord(Byte::get($buffer, 4));
        $server[SParams::CONVARS]['gamemode']   = ord(Byte::get($buffer, 4));
        $server[SParams::CONVARS]['motd']       = ParseString::get(Pascal::get($buffer, 4, 3, -3));
        $server[SParams::CONVARS]['respawns']   = ord(Byte::get($buffer, 4));
        $server[SParams::CONVARS]['time_limit'] = Time::get(Unpack::get(Byte::get($buffer, 4), "f"));
        $server[SParams::CONVARS]['voting']     = ord(Byte::get($buffer, 4));

        $buffer = substr($buffer, 2);

        //---------------------------------------------------------+

        for ($player_key=0; $player_key<$server[SParams::SERVER]['players']; $player_key++) {
            $server[SParams::PLAYERS][$player_key]['team'] = ord(Byte::get($buffer, 4));

            $unknown = ord(Byte::get($buffer, 1));
        }

        //---------------------------------------------------------+

        $buffer = substr($buffer, 7);

        $server[SParams::CONVARS]['platoon_1_color']   = ord(Byte::get($buffer, 8));
        $server[SParams::CONVARS]['platoon_2_color']   = ord(Byte::get($buffer, 8));
        $server[SParams::CONVARS]['platoon_3_color']   = ord(Byte::get($buffer, 8));
        $server[SParams::CONVARS]['platoon_4_color']   = ord(Byte::get($buffer, 8));
        $server[SParams::CONVARS]['timer_on']          = ord(Byte::get($buffer, 1));
        $server[SParams::CONVARS]['timer_time']        = Time::get(Unpack::get(Byte::get($buffer, 4), "f"));
        $server[SParams::CONVARS]['time_debriefing']   = Time::get(Unpack::get(Byte::get($buffer, 4), "f"));
        $server[SParams::CONVARS]['time_respawn_min']  = Time::get(Unpack::get(Byte::get($buffer, 4), "f"));
        $server[SParams::CONVARS]['time_respawn_max']  = Time::get(Unpack::get(Byte::get($buffer, 4), "f"));
        $server[SParams::CONVARS]['time_respawn_safe'] = Time::get(Unpack::get(Byte::get($buffer, 4), "f"));
        $server[SParams::CONVARS]['difficulty']        = ord(Byte::get($buffer, 4));
        $server[SParams::CONVARS]['respawn_total']     = ord(Byte::get($buffer, 4));
        $server[SParams::CONVARS]['random_insertions'] = ord(Byte::get($buffer, 1));
        $server[SParams::CONVARS]['spectators']        = ord(Byte::get($buffer, 1));
        $server[SParams::CONVARS]['arcademode']        = ord(Byte::get($buffer, 1));
        $server[SParams::CONVARS]['ai_backup']         = ord(Byte::get($buffer, 1));
        $server[SParams::CONVARS]['random_teams']      = ord(Byte::get($buffer, 1));
        $server[SParams::CONVARS]['time_starting']     = Time::get(Unpack::get(Byte::get($buffer, 4), "f"));
        $server[SParams::CONVARS]['identify_friends']  = ord(Byte::get($buffer, 1));
        $server[SParams::CONVARS]['identify_threats']  = ord(Byte::get($buffer, 1));

        $buffer = substr($buffer, 5);

        $server[SParams::CONVARS]['restrictions']      = ParseString::get(Pascal::get($buffer, 4, 3, -3));

        //---------------------------------------------------------+

        switch ($server[SParams::CONVARS]['status']) {
            case 3:{
                $server[SParams::CONVARS]['status'] = "Joining";
                break;
            }
            case 4:{
                $server[SParams::CONVARS]['status'] = "Joining";
                break;
            }
            case 5:{
                $server[SParams::CONVARS]['status'] = "Joining";
                break;
            }
        }

        switch ($server[SParams::CONVARS]['gamemode']) {
            case 2: {
                $server[SParams::CONVARS]['gamemode'] = "Co-Op";
                break;
            }
            case 3: {
                $server[SParams::CONVARS]['gamemode'] = "Solo";
                break;
            }
            case 4: {
                $server[SParams::CONVARS]['gamemode'] = "Team";
                break;
            }
        }

        switch ($server[SParams::CONVARS]['respawns']) {
            case 0: {
                $server[SParams::CONVARS]['respawns'] = "None";
                break;
            }
            case 1: {
                $server[SParams::CONVARS]['respawns'] = "Individual";
                break;
            }
            case 2: {
                $server[SParams::CONVARS]['respawns'] = "Team";
                break;
            }
            case 3: {
                $server[SParams::CONVARS]['respawns'] = "Infinite";
                break;
            }
        }

        switch ($server[SParams::CONVARS]['difficulty']) {
            case 0: {
                $server[SParams::CONVARS]['difficulty'] = "Recruit";
                break;
            }
            case 1: {
                $server[SParams::CONVARS]['difficulty'] = "Veteran";
                break;
            }
            case 2: {
                $server[SParams::CONVARS]['difficulty'] = "Elite";
                break;
            }
        }

        //---------------------------------------------------------+

        return true;
    }

}
