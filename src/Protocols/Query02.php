<?php

namespace ZekyWolf\LGSQ\Protocols;

use ZekyWolf\LGSQ\Helpers\{
    Parse\Colors,
    Parse\Time,
    EServerParams as SParams,
    EConnectionParams as CParams
};

class Query02
{
    public static function get(&$server, &$lgsl_need, &$lgsl_fp)
    {
        //---------------------------------------------------------+

        if($server[SParams::BASIC][CParams::TYPE] == "quake2") {
            fwrite($lgsl_fp, "\xFF\xFF\xFF\xFFstatus");
        } elseif ($server[SParams::BASIC][CParams::TYPE] == "warsowold") {
            fwrite($lgsl_fp, "\xFF\xFF\xFF\xFFgetinfo");
        } elseif ($server[SParams::BASIC][CParams::TYPE] == "callofdutyiw") {
            fwrite($lgsl_fp, "\xFF\xFF\xFF\xFFgetinfo LGSL");
        } // IW6x
        elseif (strpos($server[SParams::BASIC][CParams::TYPE], "moh") !== false) {
            fwrite($lgsl_fp, "\xFF\xFF\xFF\xFF\x02getstatus");
        } // mohaa_ mohaab_ mohaas_ mohpa_
        else {
            fwrite($lgsl_fp, "\xFF\xFF\xFF\xFFgetstatus");
        }

        $buffer = fread($lgsl_fp, 4096);

        if (!$buffer) {
            return false;
        }

        //---------------------------------------------------------+

        $part = explode("\n", $buffer);  // SPLIT INTO PARTS: HEADER/SETTINGS/PLAYERS/FOOTER
        if ($server[SParams::BASIC][CParams::TYPE] !== "callofdutyiw") {
            array_pop($part);              // REMOVE FOOTER WHICH IS EITHER NULL OR "\challenge\"
        }
        $item = explode("\\", $part[1]); // SPLIT PART INTO ITEMS

        $s = 1;
        if ($item[0]) {
            $s = 0;
        } // IW4 HAS NO EXTRA "\"
        for ($i = $s; $i < count($item); $i += 2) { // SKIP EVEN KEYS
            $data_key               = strtolower(Colors::get($item[$i], "1"));
            $server[SParams::CONVARS][$data_key] = Colors::get($item[$i+1], "1");
        }

        //---------------------------------------------------------+

        if (!empty($server[SParams::CONVARS]['hostname'])) {
            $server[SParams::SERVER]['name'] = $server[SParams::CONVARS]['hostname'];
        }
        if (!empty($server[SParams::CONVARS]['sv_hostname'])) {
            $server[SParams::SERVER]['name'] = $server[SParams::CONVARS]['sv_hostname'];
        }

        if (isset($server[SParams::CONVARS]['gamename'])) {
            $server[SParams::SERVER]['game'] = $server[SParams::CONVARS]['gamename'];
        }
        if (isset($server[SParams::CONVARS]['protocol'])) {
            switch($server[SParams::CONVARS]['protocol']) {
                case '6':     $server[SParams::SERVER]['game'] = 'iw3';
                    break;
                case '20604': $server[SParams::SERVER]['game'] = 'iw5';
                    break;
                case '101':   $server[SParams::SERVER]['game'] = 'iw6x';
                    break;
            }
        }
        if (isset($server[SParams::CONVARS]['mapname'])) {
            $server[SParams::SERVER]['map']  = $server[SParams::CONVARS]['mapname'];
        }

        $server[SParams::SERVER]['players'] = empty($part['2']) ? 0 : count($part) - 2;

        if (isset($server[SParams::CONVARS]['maxclients'])) {
            $server[SParams::SERVER]['playersmax'] = $server[SParams::CONVARS]['maxclients'];
        }    // QUAKE 2
        if (isset($server[SParams::CONVARS]['sv_maxclients'])) {
            $server[SParams::SERVER]['playersmax'] = $server[SParams::CONVARS]['sv_maxclients'];
        }

        if (isset($server[SParams::CONVARS]['pswrd'])) {
            $server[SParams::SERVER]['password'] = $server[SParams::CONVARS]['pswrd'];
        }              // CALL OF DUTY
        if (isset($server[SParams::CONVARS]['needpass'])) {
            $server[SParams::SERVER]['password'] = $server[SParams::CONVARS]['needpass'];
        }           // QUAKE 2
        if (isset($server[SParams::CONVARS]['g_needpass'])) {
            $server[SParams::SERVER]['password'] = (int)$server[SParams::CONVARS]['g_needpass'];
        }

        array_shift($part); // REMOVE HEADER
        array_shift($part); // REMOVE SETTING

        //---------------------------------------------------------+

        if ($server[SParams::BASIC][CParams::TYPE] == "nexuiz") {
            // (SCORE) (PING) (TEAM IF TEAM GAME) "(NAME)"
            $pattern = "/(.*) (.*) (.*)\"(.*)\"/U";
            $fields = [ 1 => "score", 2 => "ping", 3 => "team", 4 => "name" ];
        } elseif ($server[SParams::BASIC][CParams::TYPE] == "warsow") {
            // (SCORE) (PING) "(NAME)" (TEAM)
            $pattern = "/(.*) (.*) \"(.*)\" (.*)/";
            $fields = [ 1 => "score", 2 => "ping", 3 => "name", 4 => "team" ];
        } elseif ($server[SParams::BASIC][CParams::TYPE] == "sof2") {
            // (SCORE) (PING) "(NAME)"
            $pattern = "/(.*) (.*) \"(.*)\"/";
            $fields = [ 1 => "score", 2 => "ping", 3 => "name" ];
        } elseif (strpos($server[SParams::BASIC][CParams::TYPE], "mohpa") !== false) {
            // (?) (SCORE) (?) (TIME) (?) "(RANK?)" "(NAME)"
            $pattern = "/(.*) (.*) (.*) (.*) (.*) \"(.*)\" \"(.*)\"/";
            $fields = [ 2 => "score", 3 => "deaths", 4 => "time", 6 => "rank", 7 => "name" ];
        } elseif (strpos($server[SParams::BASIC][CParams::TYPE], "moh") !== false) {
            // (PING) "(NAME)"
            $pattern = "/(.*) \"(.*)\"/";
            $fields = [ 1 => "ping", 2 => "name" ];
        } else {
            // (SCORE) (PING) "(NAME)"
            $pattern = "/(.*) (.*) \"(.*)\"/";
            $fields = [ 1 => "score", 2 => "ping", 3 => "name" ];
        }

        //---------------------------------------------------------+

        foreach ($part as $player_key => $data) {
            if (!$data) {
                continue;
            }

            preg_match($pattern, $data, $match);

            foreach ($fields as $match_key => $field_name) {
                if (isset($match[$match_key])) {
                    $server[SParams::PLAYERS][$player_key][$field_name] = trim($match[$match_key]);
                }
            }

            $server[SParams::PLAYERS][$player_key]['name'] = Colors::get($server[SParams::PLAYERS][$player_key]['name'], "1");

            if (isset($server[SParams::PLAYERS][$player_key]['time'])) {
                $server[SParams::PLAYERS][$player_key]['time'] = Time::get($server[SParams::PLAYERS][$player_key]['time']);
            }
        }

        //---------------------------------------------------------+

        return true;
    }
}
