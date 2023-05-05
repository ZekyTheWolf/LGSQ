<?php

namespace ZekyWolf\LGSQ\Protocols;

use ZekyWolf\LGSQ\Helpers\EServerParams as SParams;

class Query04
{
    public static function get(&$server, &$lgsl_need, &$lgsl_fp)
    {
        //---------------------------------------------------------+

        fwrite($lgsl_fp, "REPORT");

        $buffer = fread($lgsl_fp, 4096);

        if (!$buffer) {
            return false;
        }

        //---------------------------------------------------------+

        $lgsl_ravenshield_key = [
            "A1" => "playersmax",
            "A2" => "tkpenalty",
            "B1" => "players",
            "B2" => "allowradar",
            "D2" => "version",
            "E1" => "mapname",
            "E2" => "lid",
            "F1" => "maptype",
            "F2" => "gid",
            "G1" => "password",
            "G2" => "hostport",
            "H1" => "dedicated",
            "H2" => "terroristcount",
            "I1" => "hostname",
            "I2" => "aibackup",
            "J1" => "mapcycletypes",
            "J2" => "rotatemaponsuccess",
            "K1" => "mapcycle",
            "K2" => "forcefirstpersonweapons",
            "L1" => "players_name",
            "L2" => "gamename",
            "L3" => "punkbuster",
            "M1" => "players_time",
            "N1" => "players_ping",
            "O1" => "players_score",
            "P1" => "queryport",
            "Q1" => "rounds",
            "R1" => "roundtime",
            "S1" => "bombtimer",
            "T1" => "bomb",
            "W1" => "allowteammatenames",
            "X1" => "iserver",
            "Y1" => "friendlyfire",
            "Z1" => "autobalance"
        ];

        //---------------------------------------------------------+

        $item = explode("\xB6", $buffer);

        foreach ($item as $data_value) {
            $tmp = explode(" ", $data_value, 2);
            $data_key = isset($lgsl_ravenshield_key[$tmp[0]]) ? $lgsl_ravenshield_key[$tmp[0]] : $tmp[0]; // CONVERT TO DESCRIPTIVE KEYS
            $server[SParams::CONVARS][$data_key] = trim($tmp[1]); // ALL VALUES NEED TRIMMING
        }

        $server[SParams::CONVARS]['mapcycle']      = str_replace("/", " ", $server[SParams::CONVARS]['mapcycle']);      // CONVERT SLASH TO SPACE
        $server[SParams::CONVARS]['mapcycletypes'] = str_replace("/", " ", $server[SParams::CONVARS]['mapcycletypes']); // SO LONG LISTS WRAP

        //---------------------------------------------------------+

        $server[SParams::SERVER]['game']       = $server[SParams::CONVARS]['gamename'];
        $server[SParams::SERVER]['name']       = $server[SParams::CONVARS]['hostname'];
        $server[SParams::SERVER]['map']        = $server[SParams::CONVARS]['mapname'];
        $server[SParams::SERVER]['players']    = $server[SParams::CONVARS]['players'];
        $server[SParams::SERVER]['playersmax'] = $server[SParams::CONVARS]['playersmax'];
        $server[SParams::SERVER]['password']   = $server[SParams::CONVARS]['password'];

        //---------------------------------------------------------+

        $player_name  = isset($server[SParams::CONVARS]['players_name']) ? explode("/", substr($server[SParams::CONVARS]['players_name'], 1)) : [];
        unset($server[SParams::CONVARS]['players_name']);

        $player_time  = isset($server[SParams::CONVARS]['players_time']) ? explode("/", substr($server[SParams::CONVARS]['players_time'], 1)) : [];
        unset($server[SParams::CONVARS]['players_time']);

        $player_ping  = isset($server[SParams::CONVARS]['players_ping']) ? explode("/", substr($server[SParams::CONVARS]['players_ping'], 1)) : [];
        unset($server[SParams::CONVARS]['players_ping']);

        $player_score = isset($server[SParams::CONVARS]['players_score']) ? explode("/", substr($server[SParams::CONVARS]['players_score'], 1)) : [];
        unset($server[SParams::CONVARS]['players_score']);

        foreach ($player_name as $key => $name) {
            $server[SParams::PLAYERS][$key]['name']  = $player_name[$key];
            $server[SParams::PLAYERS][$key]['time']  = $player_time[$key];
            $server[SParams::PLAYERS][$key]['ping']  = $player_ping[$key];
            $server[SParams::PLAYERS][$key]['score'] = $player_score[$key];
        }

        //---------------------------------------------------------+

        return true;
    }
}
