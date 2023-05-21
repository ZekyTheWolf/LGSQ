<?php

namespace ZekyWolf\LGSQ\Protocols;

use ZekyWolf\LGSQ\Helpers\{
    Parse\ParseString,
    ERequestParams as RParams,
    EServerParams as SParams,
    EConnectionParams as CParams
};

class Query03
{
    public static function get(&$server, &$lgsl_need, &$lgsl_fp)
    {
        //---------------------------------------------------------+

        // BF1942 BUG: RETURNS 'GHOST' NAMES - TO SKIP THESE WE NEED AN [s] REQUEST FOR AN ACCURATE PLAYER COUNT
        if (
            $server[SParams::BASIC][CParams::TYPE] == "bf1942" &&
            $lgsl_need[RParams::PLAYERS] &&
            !$lgsl_need[RParams::SERVER] &&
            !isset($lgsl_need[RParams::SERVER]) &&
            !isset($lgsl_need[RParams::PLAYERS])
        ) {
            $lgsl_need[RParams::SERVER] = true;
            $lgsl_need[RParams::PLAYERS] = true;
        }

        if($server[SParams::BASIC][CParams::TYPE] == "cncrenegade") {
            fwrite($lgsl_fp, "\\status\\");
        } elseif($lgsl_need[RParams::SERVER] || $lgsl_need[RParams::CONVARS]) {
            fwrite($lgsl_fp, "\\basic\\\\info\\\\rules\\");
            $lgsl_need[RParams::SERVER] = false;
            $lgsl_need[RParams::CONVARS] = false;
        } elseif ($lgsl_need[RParams::PLAYERS]) {
            fwrite($lgsl_fp, "\\players\\");
            $lgsl_need[RParams::PLAYERS] = false;
        }

        //---------------------------------------------------------+
        $buffer = "";
        $packet_count = 0;
        $packet_total = 20;
        $queryid = 0;

        do {
            $packet = fread($lgsl_fp, 4096);

            // QUERY PORT CHECK AS THE CONNECTION PORT WILL ALSO RESPOND
            if (strpos($packet, "\\") === false) {
                return false;
            }

            // REMOVE SLASH PREFIX
            if ($packet[0] == "\\") {
                $packet = substr($packet, 1);
            }

            $queryid;

            while ($packet) {
                $key   = strtolower(ParseString::get($packet, 0, "\\"));
                $value =       trim(ParseString::get($packet, 0, "\\"));

                // CHECK IF KEY IS PLAYER DATA
                if (preg_match("/(.*)_([0-9]+)$/", $key, $match)) {
                    // SEPERATE TEAM NAMES
                    if ($match[1] == "teamname") {
                        $server[SParams::TEAMS][$match[2]]['name'] = $value;
                        continue;
                    }

                    // CONVERT TO LGSL STANDARD
                    if($match[1] == "player") {
                        $match[1] = "name";
                    } elseif($match[1] == "playername") {
                        $match[1] = "name";
                    } elseif($match[1] == "frags") {
                        $match[1] = "score";
                    } elseif($match[1] == "ngsecret") {
                        $match[1] = "stats";
                    }

                    $server[RParams::PLAYERS][$match[2]][$match[1]] = $value;
                    continue;
                }

                // SEPERATE QUERYID
                if ($key == "queryid") {
                    $queryid = $value;
                    continue;
                }

                // SERVER SETTING
                $server[SParams::CONVARS][$key] = $value;
            }

            // FINAL PACKET NUMBER IS THE TOTAL
            if (isset($server[SParams::CONVARS]['final'])) {
                preg_match("/([0-9]+)\.([0-9]+)/", $queryid, $match);
                $packet_total = intval($match[2]);
                unset($server[SParams::CONVARS]['final']);
            }

            $packet_count ++;
        } while ($packet_count < $packet_total);

        //---------------------------------------------------------+

        if (isset($server[SParams::CONVARS]['mapname'])) {
            $server[SParams::SERVER]['map'] = $server[SParams::CONVARS]['mapname'];

            if (!empty($server[SParams::CONVARS]['hostname'])) {
                $server[SParams::SERVER]['name'] = $server[SParams::CONVARS]['hostname'];
            }
            if (!empty($server[SParams::CONVARS]['sv_hostname'])) {
                $server[SParams::SERVER]['name'] = $server[SParams::CONVARS]['sv_hostname'];
            }

            if (isset($server[SParams::CONVARS]['password'])) {
                $server[SParams::SERVER]['password']   = $server[SParams::CONVARS]['password'];
            }
            if (isset($server[SParams::CONVARS]['numplayers'])) {
                $server[SParams::SERVER]['players']    = $server[SParams::CONVARS]['numplayers'];
            }
            if (isset($server[SParams::CONVARS]['maxplayers'])) {
                $server[SParams::SERVER]['playersmax'] = $server[SParams::CONVARS]['maxplayers'];
            }

            if (!empty($server[SParams::CONVARS]['gamename'])) {
                $server[SParams::SERVER]['game'] = $server[SParams::CONVARS]['gamename'];
            }
            if (!empty($server[SParams::CONVARS]['gameid']) && empty($server[SParams::CONVARS]['gamename'])) {
                $server[SParams::SERVER]['game'] = $server[SParams::CONVARS]['gameid'];
            }
            if (!empty($server[SParams::CONVARS]['gameid']) && $server[SParams::BASIC][CParams::TYPE] == "bf1942") {
                $server[SParams::SERVER]['game'] = $server[SParams::CONVARS]['gameid'];
            }
        }

        //---------------------------------------------------------+

        if ($server[SParams::PLAYERS]) {
            // BF1942 BUG - REMOVE 'GHOST' PLAYERS
            if ($server[SParams::BASIC][CParams::TYPE] == "bf1942" && $server[SParams::SERVER]['players']) {
                $server[SParams::PLAYERS] = array_slice(
                    $server[SParams::PLAYERS],
                    0,
                    $server[SParams::SERVER]['players']
                );
            }

            // OPERATION FLASHPOINT BUG: 'GHOST' PLAYERS IN UN-USED 'TEAM' FIELD
            if ($server[SParams::PLAYERS]['type'] == "flashpoint") {
                foreach ($server[SParams::PLAYERS] as $key => $value) {
                    unset($server[SParams::PLAYERS][$key]['team']);
                }
            }

            // AVP2 BUG: PLAYER NUMBER PREFIXED TO NAMES
            if ($server[SParams::BASIC][CParams::TYPE] == "avp2") {
                foreach ($server[SParams::BASIC] as $key => $value) {
                    $server[SParams::BASIC][$key]['name'] = preg_replace(
                        "/[0-9]+~/",
                        "",
                        $server[SParams::BASIC][$key]['name']
                    );
                }
            }

            // IF TEAM NAMES AVAILABLE USED INSTEAD OF TEAM NUMBERS
            if (isset($server[SParams::TEAMS][0]['name'])) {
                foreach ($server[SParams::PLAYERS] as $key => $value) {
                    $team_key = $server[SParams::PLAYERS][$key]['team'] - 1;
                    $server[SParams::PLAYERS][$key]['team'] = $server[SParams::TEAMS][$team_key]['name'];
                }
            }

            // RE-INDEX PLAYER KEYS TO REMOVE ANY GAPS
            $server[SParams::PLAYERS] = array_values($server[SParams::PLAYERS]);
        }

        return true;
    }
}
