<?php

namespace ZekyWolf\LGSQ\Protocols;

use ZekyWolf\LGSQ\Helpers\{
    Parse\Pascal,
    Parse\Unpack,
    EServerParams as SParams,
    ERequestParams as RParams
};

class Query30
{
    public static function get(&$server, &$lgsl_need, &$lgsl_fp)
    {
        //---------------------------------------------------------+
        //  REFERENCE: http://blogs.battlefield.ea.com/battlefield_bad_company/archive/2010/02/05/remote-administration-interface-for-bfbc2-pc.aspx
        //  THIS USES TCP COMMUNICATION

        if ($lgsl_need[RParams::SERVER] || $lgsl_need[RParams::CONVARS]) {
            fwrite($lgsl_fp, "\x00\x00\x00\x00\x1B\x00\x00\x00\x01\x00\x00\x00\x0A\x00\x00\x00serverInfo\x00");
        } elseif ($lgsl_need[RParams::PLAYERS]) {
            fwrite($lgsl_fp, "\x00\x00\x00\x00\x24\x00\x00\x00\x02\x00\x00\x00\x0B\x00\x00\x00listPlayers\x00\x03\x00\x00\x00all\x00");
        }

        //---------------------------------------------------------+

        $buffer = fread($lgsl_fp, 4096);

        if (!$buffer) {
            return false;
        }

        $length = Unpack::get(substr($buffer, 4, 4), "L");

        while (strlen($buffer) < $length) {
            $packet = fread($lgsl_fp, 4096);

            if ($packet) {
                $buffer .= $packet;
            } else {
                break;
            }
        }

        //---------------------------------------------------------+

        $buffer = substr($buffer, 12); // REMOVE HEADER

        $response_type = Pascal::get($buffer, 4, 0, 1);

        if ($response_type != "OK") {
            return false;
        }

        //---------------------------------------------------------+

        if ($lgsl_need[RParams::SERVER] || $lgsl_need[RParams::CONVARS]) {
            $lgsl_need[RParams::SERVER] = false;
            $lgsl_need[RParams::CONVARS] = false;

            $server[SParams::SERVER]['name']            = Pascal::get($buffer, 4, 0, 1);
            $server[SParams::SERVER]['players']         = Pascal::get($buffer, 4, 0, 1);
            $server[SParams::SERVER]['playersmax']      = Pascal::get($buffer, 4, 0, 1);
            $server[SParams::CONVARS]['gamemode']        = Pascal::get($buffer, 4, 0, 1);
            $server[SParams::SERVER]['map']             = Pascal::get($buffer, 4, 0, 1);
            $server[SParams::CONVARS]['score_attackers'] = Pascal::get($buffer, 4, 0, 1);
            $server[SParams::CONVARS]['score_defenders'] = Pascal::get($buffer, 4, 0, 1);

            // CONVERT MAP NUMBER TO DESCRIPTIVE NAME

            $server[SParams::CONVARS]['level'] = $server[SParams::SERVER]['map'];
            $map_check = strtolower($server[SParams::SERVER]['map']);

            if (strpos($map_check, "mp_001") !== false) {
                $server[SParams::SERVER]['map'] = "Panama Canal";
            } elseif (strpos($map_check, "mp_002") !== false) {
                $server[SParams::SERVER]['map'] = "Valparaiso";
            } elseif (strpos($map_check, "mp_003") !== false) {
                $server[SParams::SERVER]['map'] = "Laguna Alta";
            } elseif (strpos($map_check, "mp_004") !== false) {
                $server[SParams::SERVER]['map'] = "Isla Inocentes";
            } elseif (strpos($map_check, "mp_005") !== false) {
                $server[SParams::SERVER]['map'] = "Atacama Desert";
            } elseif (strpos($map_check, "mp_006") !== false) {
                $server[SParams::SERVER]['map'] = "Arica Harbor";
            } elseif (strpos($map_check, "mp_007") !== false) {
                $server[SParams::SERVER]['map'] = "White Pass";
            } elseif (strpos($map_check, "mp_008") !== false) {
                $server[SParams::SERVER]['map'] = "Nelson Bay";
            } elseif (strpos($map_check, "mp_009") !== false) {
                $server[SParams::SERVER]['map'] = "Laguna Presa";
            } elseif (strpos($map_check, "mp_012") !== false) {
                $server[SParams::SERVER]['map'] = "Port Valdez";
            }
        }

        //---------------------------------------------------------+

        elseif ($lgsl_need[RParams::PLAYERS]) {
            $lgsl_need[RParams::PLAYERS] = false;

            $field_total = Pascal::get($buffer, 4, 0, 1);
            $field_list  = [];

            for ($i=0; $i<$field_total; $i++) {
                $field_list[] = strtolower(Pascal::get($buffer, 4, 0, 1));
            }

            $player_squad = [ "","Alpha","Bravo","Charlie","Delta","Echo","Foxtrot","Golf","Hotel" ];
            $player_team  = [ "","Attackers","Defenders" ];
            $player_total = Pascal::get($buffer, 4, 0, 1);

            for ($i=0; $i<$player_total; $i++) {
                foreach ($field_list as $field) {
                    $value = Pascal::get($buffer, 4, 0, 1);

                    switch ($field) {
                        case "clantag": {
                            $server[SParams::PLAYERS][$i]['name']  = $value;
                            break;
                        }
                        case "name": {
                            $server[SParams::PLAYERS][$i]['name']  = empty($server[SParams::PLAYERS][$i]['name']) ? $value : "[{$server[SParams::PLAYERS][$i]['name']}] {$value}";
                            break;
                        }
                        case "teamid": {
                            $server[SParams::PLAYERS][$i]['team']  = isset($player_team[$value]) ? $player_team[$value] : $value;
                            break;
                        }
                        case "squadid": {
                            $server[SParams::PLAYERS][$i]['squad'] = isset($player_squad[$value]) ? $player_squad[$value] : $value;
                            break;
                        }
                        default:{
                            $server[SParams::PLAYERS][$i][$field]  = $value;
                            break;
                        }
                    }
                }
            }
        }

        //---------------------------------------------------------+

        return true;
    }
}
