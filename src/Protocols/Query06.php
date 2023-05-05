<?php

namespace ZekyWolf\LGSQ\Protocols;

use ZekyWolf\LGSQ\Helpers\{
    Parse\Byte,
    Parse\Colors,
    Parse\ParseString,
    EServerParams as SParams
};

class Query06
{
    public static function get(&$server, &$lgsl_need, &$lgsl_fp)
    {
        //---------------------------------------------------------+
        //  GET A CHALLENGE CODE IF NEEDED

        $challenge_code = "";

        if ($server[SParams::BASIC]['type'] != "bf2" && $server[SParams::BASIC]['type'] != "graw") {
            fwrite($lgsl_fp, "\xFE\xFD\x09\x21\x21\x21\x21\xFF\xFF\xFF\x01");

            $challenge_packet = fread($lgsl_fp, 4096);

            if (!$challenge_packet) {
                return false;
            }

            $challenge_code = substr($challenge_packet, 5, -1); // REMOVE HEADER AND TRAILING NULL

            // IF CODE IS RETURNED ( SOME STALKER SERVERS RETURN BLANK WHERE THE CODE IS NOT NEEDED )
            // CONVERT DECIMAL |TO| HEX AS 8 CHARACTER STRING |TO| 4 PAIRS OF HEX |TO| 4 PAIRS OF DECIMAL |TO| 4 PAIRS OF ASCII

            $challenge_code = $challenge_code ? chr($challenge_code >> 24).chr($challenge_code >> 16).chr($challenge_code >> 8).chr($challenge_code >> 0) : "";
        }

        fwrite($lgsl_fp, "\xFE\xFD\x00\x21\x21\x21\x21{$challenge_code}\xFF\xFF\xFF\x01");

        //---------------------------------------------------------+
        //  GET RAW PACKET DATA

        $buffer = [];
        $packet_count = 0;
        $packet_total = 4;

        do {
            $packet_count ++;
            $packet = fread($lgsl_fp, 8192);

            if (!$packet) {
                return false;
            }

            $packet       = substr($packet, 14); // REMOVE SPLITNUM HEADER
            $packet_order = ord(Byte::get($packet, 1));

            if ($packet_order >= 128) { // LAST PACKET - SO ITS ORDER NUMBER IS ALSO THE TOTAL
                $packet_order -= 128;
                $packet_total = $packet_order + 1;
            }

            $buffer[$packet_order] = $packet;
            if ($server[SParams::BASIC]['type'] == "minecraft" || $server[SParams::BASIC]['type'] == "jc2mp") {
                $packet_total = 1;
            }

        } while ($packet_count < $packet_total);

        //---------------------------------------------------------+
        //  PROCESS AND SORT PACKETS

        foreach ($buffer as $key => $packet) {
            $packet = substr($packet, 0, -1); // REMOVE END NULL FOR JOINING

            if (substr($packet, -1) != "\x00") { // LAST VALUE HAS BEEN SPLIT
                $part = explode("\x00", $packet); // REMOVE SPLIT VALUE AS COMPLETE VALUE IS IN NEXT PACKET
                array_pop($part);
                $packet = implode("\x00", $part)."\x00";
            }

            if ($packet[0] != "\x00") { // PLAYER OR TEAM DATA THAT MAY BE A CONTINUATION
                $pos = strpos($packet, "\x00") + 1; // WHEN DATA IS SPLIT THE NEXT PACKET STARTS WITH A REPEAT OF THE FIELD NAME

                if (isset($packet[$pos]) && $packet[$pos] != "\x00") { // REPEATED FIELD NAMES END WITH \x00\x?? INSTEAD OF \x00\x00
                    $packet = substr($packet, $pos + 1); // REMOVE REPEATED FIELD NAME
                } else {
                    $packet = "\x00".$packet; // RE-ADD NULL AS PACKET STARTS WITH A NEW FIELD
                }
            }

            $buffer[$key] = $packet;
        }

        ksort($buffer);

        $buffer = implode("", $buffer);

        //---------------------------------------------------------+
        //  SERVER SETTINGS

        $buffer = substr($buffer, 1); // REMOVE HEADER \x00

        while ($key = strtolower(ParseString::get($buffer))) {
            $server[SParams::CONVARS][$key] = ParseString::get($buffer);
        }

        $lgsl_conversion = [
            "hostname" => "name",
            "gamename" => "game",
            "mapname" => "map",
            "map" => "map",
            "numplayers" => "players",
            "maxplayers" => "playersmax",
            "password" => "password"
        ];
        foreach ($lgsl_conversion as $e => $s) {
            if (isset($server[SParams::CONVARS][$e])) {
                $server[SParams::SERVER][$s] = $server[SParams::CONVARS][$e];
                unset($server[SParams::CONVARS][$e]);
            }
        }

        if ($server[SParams::BASIC]['type'] == "bf2" || $server[SParams::BASIC]['type'] == "bf2142") {
            $server[SParams::SERVER]['map'] = ucwords(str_replace("_", " ", $server[SParams::SERVER]['map']));
        } // MAP NAME CONSISTENCY
        elseif ($server[SParams::BASIC]['type'] == "jc2mp") {
            $server[SParams::SERVER]['map'] = 'Panau';
        } elseif ($server[SParams::BASIC]['type'] == "minecraft") {
            if (isset($server[SParams::CONVARS]['gametype'])) {
                $server[SParams::SERVER]['game'] = strtolower($server[SParams::CONVARS]['game_id']);
            }
            $server[SParams::SERVER]['name'] = Colors::get($server[SParams::SERVER]['name'], "minecraft");
            foreach ($server[SParams::CONVARS] as $key => $val) {
                if (($key != 'version') && ($key != 'plugins') && ($key != 'whitelist')) {
                    unset($server[SParams::CONVARS][$key]);
                }
            }

            $plugins = explode(": ", $server[SParams::CONVARS]['plugins'], 2);
            if ($plugins[0]) {
                $server[SParams::CONVARS]['plugins'] = $plugins[0];
            } else {
                $server[SParams::CONVARS]['plugins'] = 'none (Vanilla)';
            }
            if (count($plugins) == 2) {
                while ($key = ParseString::get($plugins[1], 0, " ")) {
                    $server[SParams::CONVARS][$key] = ParseString::get($plugins[1], 0, "; ");
                }
            }
            $buffer = $buffer."\x00"; // Needed to correctly terminate the players list
        }

        if ($server[SParams::SERVER]['players'] == "0") {
            return true;
        } // IF SERVER IS EMPTY SKIP THE PLAYER CODE

        //---------------------------------------------------------+
        //  PLAYER DETAILS

        $buffer = substr($buffer, 1); // REMOVE HEADER \x01

        while ($buffer) {
            if ($buffer[0] == "\x02") {
                break;
            }
            if ($buffer[0] == "\x00") {
                $buffer = substr($buffer, 1);
                break;
            }

            $field = ParseString::get($buffer, 0, "\x00\x00");
            $field = strtolower(substr($field, 0, -1));

            if     ($field == "player") {
                $field = "name";
            } elseif ($field == "aibot") {
                $field = "bot";
            }

            if ($buffer[0] == "\x00") {
                $buffer = substr($buffer, 1);
                continue;
            }

            $value_list = ParseString::get($buffer, 0, "\x00\x00");
            $value_list = explode("\x00", $value_list);

            foreach ($value_list as $key => $value) {
                $server[SParams::PLAYERS][$key][$field] = $value;
            }
        }

        //---------------------------------------------------------+
        //  TEAM DATA

        $buffer = substr($buffer, 1); // REMOVE HEADER \x02

        while ($buffer) {
            if ($buffer[0] == "\x00") {
                break;
            }

            $field = ParseString::get($buffer, 0, "\x00\x00");
            $field = strtolower($field);

            if     ($field == "team_t") {
                $field = "name";
            } elseif ($field == "score_t") {
                $field = "score";
            }

            $value_list = ParseString::get($buffer, 0, "\x00\x00");
            $value_list = explode("\x00", $value_list);

            foreach ($value_list as $key => $value) {
                $server[SParams::TEAMS][$key][$field] = $value;
            }
        }

        //---------------------------------------------------------+
        //  TEAM NAME CONVERSION

        if(
            $server[SParams::PLAYERS] &&
            isset($server[SParams::TEAMS][0]['name']) &&
            $server[SParams::TEAMS][0]['name'] != "Team"
        ) {
            foreach ($server[SParams::PLAYERS] as $key => $value) {
                if (empty($server[SParams::PLAYERS][$key]['team'])) {
                    continue;
                }

                $team_key = $server[SParams::PLAYERS][$key]['team'] - 1;

                if (!isset($server[SParams::TEAMS][$team_key]['name'])) {
                    continue;
                }

                $server[SParams::PLAYERS][$key]['team'] = $server[SParams::TEAMS][$team_key]['name'];
            }
        }

        //---------------------------------------------------------+

        return true;
    }
}
