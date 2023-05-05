<?php

namespace ZekyWolf\LGSQ\Protocols;

use ZekyWolf\LGSQ\Helpers\{
    Parse\Byte,
    Parse\Colors,
    Parse\ParseString,
    Parse\Time,
    Parse\Unpack,
    EServerParams as SParams
};

class Query10
{
    public static function get(&$server, &$lgsl_need, &$lgsl_fp)
    {
        //---------------------------------------------------------+

        if ($server[SParams::BASIC]['type'] == "quakewars") {
            fwrite($lgsl_fp, "\xFF\xFFgetInfoEX\xFF");
        } else {
            fwrite($lgsl_fp, "\xFF\xFFgetInfo\xFF");
        }

        $buffer = fread($lgsl_fp, 4096);

        if (!$buffer) {
            return false;
        }

        //---------------------------------------------------------+

        if($server[SParams::BASIC]['type'] == "wolf2009") {
            $buffer = substr($buffer, 31);
        }  // REMOVE HEADERS
        elseif ($server[SParams::BASIC]['type'] == "quakewars") {
            $buffer = substr($buffer, 33);
        } else {
            $buffer = substr($buffer, 23);
        }

        $buffer = Colors::get($buffer, "2");

        //---------------------------------------------------------+

        while ($buffer && $buffer[0] != "\x00") {
            $item_key   = strtolower(ParseString::get($buffer));
            $item_value = ParseString::get($buffer);

            $server[SParams::CONVARS][$item_key] = $item_value;
        }

        //---------------------------------------------------------+

        $buffer = substr($buffer, 2);

        $player_key = 0;

        //---------------------------------------------------------+

        if ($server[SParams::BASIC]['type'] == "wolf2009") {
            // WOLFENSTEIN: (PID)(PING)(NAME)(TAGPOSITION)(TAG)(BOT)
            while ($buffer && $buffer[0] != "\x10") { // STOPS AT PID 16
                $server[SParams::PLAYERS][$player_key]['pid']     = ord(Byte::get($buffer, 1));
                $server[SParams::PLAYERS][$player_key]['ping']    = Unpack::get(Byte::get($buffer, 2), "S");
                $server[SParams::PLAYERS][$player_key]['rate']    = Unpack::get(Byte::get($buffer, 2), "S");
                $server[SParams::PLAYERS][$player_key]['unknown'] = Unpack::get(Byte::get($buffer, 2), "S");
                $player_name                         = ParseString::get($buffer);
                $player_tag_position                 = ord(Byte::get($buffer, 1));
                $player_tag                          = ParseString::get($buffer);
                $server[SParams::PLAYERS][$player_key]['bot']     = ord(Byte::get($buffer, 1));

                if($player_tag == "") {
                    $server[SParams::PLAYERS][$player_key]['name'] = $player_name;
                } elseif ($player_tag_position == "0") {
                    $server[SParams::PLAYERS][$player_key]['name'] = $player_tag." ".$player_name;
                } else {
                    $server[SParams::PLAYERS][$player_key]['name'] = $player_name." ".$player_tag;
                }

                $player_key ++;
            }
        }

        //---------------------------------------------------------+

        elseif ($server[SParams::BASIC]['type'] == "quakewars") {
            // QUAKEWARS: (PID)(PING)(NAME)(TAGPOSITION)(TAG)(BOT)
            while ($buffer && $buffer[0] != "\x20") { // STOPS AT PID 32
                $server[SParams::PLAYERS][$player_key]['pid']  = ord(Byte::get($buffer, 1));
                $server[SParams::PLAYERS][$player_key]['ping'] = Unpack::get(Byte::get($buffer, 2), "S");
                $player_name                      = ParseString::get($buffer);
                $player_tag_position              = ord(Byte::get($buffer, 1));
                $player_tag                       = ParseString::get($buffer);
                $server[SParams::PLAYERS][$player_key]['bot']  = ord(Byte::get($buffer, 1));

                if ($player_tag_position == "") {
                    $server[SParams::PLAYERS][$player_key]['name'] = $player_name;
                } elseif ($player_tag_position == "1") {
                    $server[SParams::PLAYERS][$player_key]['name'] = $player_name." ".$player_tag;
                } else {
                    $server[SParams::PLAYERS][$player_key]['name'] = $player_tag." ".$player_name;
                }

                $player_key ++;
            }

            $buffer                      = substr($buffer, 1);
            $server[SParams::CONVARS]['si_osmask']    = Unpack::get(Byte::get($buffer, 4), "I");
            $server[SParams::CONVARS]['si_ranked']    = ord(Byte::get($buffer, 1));
            $server[SParams::CONVARS]['si_timeleft']  = Time::get(Unpack::get(Byte::get($buffer, 4), "I") / 1000);
            $server[SParams::CONVARS]['si_gamestate'] = ord(Byte::get($buffer, 1));
            $buffer                      = substr($buffer, 2);

            $player_key = 0;

            while ($buffer && $buffer[0] != "\x20") { // QUAKEWARS EXTENDED: (PID)(XP)(TEAM)(KILLS)(DEATHS)
                $server[SParams::PLAYERS][$player_key]['pid']    = ord(Byte::get($buffer, 1));
                $server[SParams::PLAYERS][$player_key]['xp']     = intval(Unpack::get(Byte::get($buffer, 4), "f"));
                $server[SParams::PLAYERS][$player_key]['team']   = ParseString::get($buffer);
                $server[SParams::PLAYERS][$player_key]['score']  = Unpack::get(Byte::get($buffer, 4), "i");
                $server[SParams::PLAYERS][$player_key]['deaths'] = Unpack::get(Byte::get($buffer, 4), "i");
                $player_key ++;
            }
        }

        //---------------------------------------------------------+

        elseif ($server[SParams::BASIC]['type'] == "quake4") {
            // QUAKE4: (PID)(PING)(RATE)(NULLNULL)(NAME)(TAG)
            while ($buffer && $buffer[0] != "\x20") { // STOPS AT PID 32
                $server[SParams::PLAYERS][$player_key]['pid']  = ord(Byte::get($buffer, 1));
                $server[SParams::PLAYERS][$player_key]['ping'] = Unpack::get(Byte::get($buffer, 2), "S");
                $server[SParams::PLAYERS][$player_key]['rate'] = Unpack::get(Byte::get($buffer, 2), "S");
                $buffer                           = substr($buffer, 2);
                $player_name                      = ParseString::get($buffer);
                $player_tag                       = ParseString::get($buffer);
                $server[SParams::PLAYERS][$player_key]['name'] = $player_tag ? $player_tag." ".$player_name : $player_name;

                $player_key ++;
            }
        }

        //---------------------------------------------------------+

        else { // DOOM3 AND PREY: (PID)(PING)(RATE)(NULLNULL)(NAME)
            while ($buffer && $buffer[0] != "\x20") { // STOPS AT PID 32
                $server[SParams::PLAYERS][$player_key]['pid']  = ord(Byte::get($buffer, 1));
                $server[SParams::PLAYERS][$player_key]['ping'] = Unpack::get(Byte::get($buffer, 2), "S");
                $server[SParams::PLAYERS][$player_key]['rate'] = Unpack::get(Byte::get($buffer, 2), "S");
                $buffer                           = substr($buffer, 2);
                $server[SParams::PLAYERS][$player_key]['name'] = ParseString::get($buffer);

                $player_key ++;
            }
        }

        //---------------------------------------------------------+

        $server[SParams::SERVER]['game']       = $server[SParams::CONVARS]['gamename'];
        $server[SParams::SERVER]['name']       = $server[SParams::CONVARS]['si_name'];
        $server[SParams::SERVER]['map']        = $server[SParams::CONVARS]['si_map'];
        $server[SParams::SERVER]['players']    = $server[SParams::PLAYERS] ? count($server[SParams::PLAYERS]) : 0;
        $server[SParams::SERVER]['playersmax'] = $server[SParams::CONVARS]['si_maxplayers'];

        if ($server[SParams::BASIC]['type'] == "wolf2009" || $server[SParams::BASIC]['type'] == "quakewars") {
            $server[SParams::SERVER]['map']      = str_replace(".entities", "", $server[SParams::SERVER]['map']);
            $server[SParams::SERVER]['password'] = $server[SParams::CONVARS]['si_needpass'];
        } else {
            $server[SParams::SERVER]['password'] = $server[SParams::CONVARS]['si_usepass'];
        }

        //---------------------------------------------------------+

        return true;
    }
}
