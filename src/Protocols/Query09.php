<?php

namespace ZekyWolf\LGSQ\Protocols;

use ZekyWolf\LGSQ\Params\{
    EServerParams as SParams,
    ERequestParams as RParams,
    EConnectionParams as CParams
};

class Query09
{
    public static function get(&$server, &$lgsl_need, &$lgsl_fp)
    {
        //---------------------------------------------------------+

        // SERIOUS SAM 2 RETURNS ALL PLAYER NAMES AS "Unknown Player" SO SKIP OR CONVERT ANY PLAYER REQUESTS
        if ($server[SParams::SERVER]['type'] == "serioussam2") {
            $lgsl_need[RParams::PLAYERS] = false;
            if (!$lgsl_need[RParams::SERVER] && !$lgsl_need[RParams::CONVARS]) {
                $lgsl_need[RParams::SERVER] = true;
            }
        }

        //---------------------------------------------------------+

        if ($lgsl_need[RParams::SERVER] || $lgsl_need[RParams::CONVARS]) {
            $lgsl_need[RParams::SERVER] = false;
            $lgsl_need[RParams::CONVARS] = false;

            fwrite($lgsl_fp, "\xFE\xFD\x00\x21\x21\x21\x21\xFF\x00\x00\x00");

            $buffer = fread($lgsl_fp, 4096);

            $buffer = substr($buffer, 5, -2); // REMOVE HEADER AND FOOTER

            if (!$buffer) {
                return false;
            }

            $item = explode("\x00", $buffer);

            foreach ($item as $item_key => $data_key) {
                if ($item_key % 2) {
                    continue;
                } // SKIP EVEN KEYS

                $data_key = strtolower($data_key);
                $server[SParams::CONVARS][$data_key] = $item[$item_key+1];
            }

            if (isset($server[SParams::CONVARS]['hostname'])) {
                $server[SParams::SERVER]['name']       = $server[SParams::CONVARS]['hostname'];
            }
            if (isset($server[SParams::CONVARS]['mapname'])) {
                $server[SParams::SERVER]['map']        = $server[SParams::CONVARS]['mapname'];
            }
            if (isset($server[SParams::CONVARS]['numplayers'])) {
                $server[SParams::SERVER]['players']    = $server[SParams::CONVARS]['numplayers'];
            }
            if (isset($server[SParams::CONVARS]['maxplayers'])) {
                $server[SParams::SERVER]['playersmax'] = $server[SParams::CONVARS]['maxplayers'];
            }
            if (isset($server[SParams::CONVARS]['password'])) {
                $server[SParams::SERVER]['password']   = $server[SParams::CONVARS]['password'];
            }

            if (!empty($server[SParams::CONVARS]['gamename'])) {
                $server[SParams::SERVER]['game'] = $server[SParams::CONVARS]['gamename'];
            }   // AARMY
            if (!empty($server[SParams::CONVARS]['gsgamename'])) {
                $server[SParams::SERVER]['game'] = $server[SParams::CONVARS]['gsgamename'];
            } // FEAR
            if (!empty($server[SParams::CONVARS]['game_id'])) {
                $server[SParams::SERVER]['game'] = $server[SParams::CONVARS]['game_id'];
            }    // BFVIETNAM

            if ($server[SParams::BASIC][CParams::TYPE] == "arma" || $server[SParams::BASIC][CParams::TYPE] == "arma2") {
                $server[SParams::SERVER]['map'] = $server[SParams::CONVARS]['mission'];
            } elseif ($server[SParams::BASIC][CParams::TYPE] == "vietcong2") {
                $server[SParams::CONVARS]['extinfo_autobalance'] = ord($server[SParams::CONVARS]['extinfo'][18]) == 2 ? "off" : "on";
                // [ 13 = Vietnam and RPG Mode 19 1b 99 9b ] [ 22 23 = Mounted MG Limit ]
                // [ 27 = Idle Limit ] [ 18 = Auto Balance ] [ 55 = Chat and Blind Spectator 5a 5c da dc ]
            }
        }

        //---------------------------------------------------------+

        elseif ($lgsl_need[RParams::PLAYERS]) {
            $lgsl_need[RParams::PLAYERS] = false;

            fwrite($lgsl_fp, "\xFE\xFD\x00\x21\x21\x21\x21\x00\xFF\x00\x00");

            $buffer = fread($lgsl_fp, 4096);

            $buffer = substr($buffer, 7, -1); // REMOVE HEADER / PLAYER TOTAL / FOOTER

            if (!$buffer) {
                return false;
            }

            if (strpos($buffer, "\x00\x00") === false) {
                return true;
            } // NO PLAYERS

            $buffer     = explode("\x00\x00", $buffer, 2);            // SPLIT FIELDS FROM ITEMS
            $buffer[0]  = str_replace("_", "", $buffer[0]); // REMOVE UNDERSCORES FROM FIELDS
            $buffer[0]  = str_replace("player", "name", $buffer[0]); // LGSL STANDARD
            $field_list = explode("\x00", $buffer[0]);                // SPLIT UP FIELDS
            $item       = explode("\x00", $buffer[1]);                // SPLIT UP ITEMS

            $item_position = 0;
            $item_total    = count($item);
            $player_key    = 0;

            do {
                foreach ($field_list as $field) {
                    $server[SParams::PLAYERS][$player_key][$field] = $item[$item_position];

                    $item_position ++;
                }

                $player_key ++;
            } while ($item_position < $item_total);
        }

        //---------------------------------------------------------+

        return true;
    }
}
