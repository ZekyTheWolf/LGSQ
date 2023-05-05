<?php

namespace ZekyWolf\LGSQ\Protocols;

use ZekyWolf\LGSQ\Helpers\{
    Parse\Byte,
    Parse\ParseString,
    Parse\Time,
    Parse\Unpack,
    ERequestParams as RParams,
    EServerParams as SParams
};

class Query05
{
    public static function get(&$server, &$lgsl_need, &$lgsl_fp)
    {
        //---------------------------------------------------------+
        //  REFERENCE: http://developer.valvesoftware.com/wiki/Server_Queries

        if ($server[SParams::BASIC]['type'] == "halflifewon") {
            if($lgsl_need[RParams::SERVER]) {
                fwrite($lgsl_fp, "\xFF\xFF\xFF\xFFdetails\x00");
            } elseif ($lgsl_need[RParams::CONVARS]) {
                fwrite($lgsl_fp, "\xFF\xFF\xFF\xFFrules\x00");
            } elseif ($lgsl_need[RParams::PLAYERS]) {
                fwrite($lgsl_fp, "\xFF\xFF\xFF\xFFplayers\x00");
            }
        } else {
            $challenge_code = isset($lgsl_need['challenge']) ? $lgsl_need['challenge'] : "\x00\x00\x00\x00";

            if($lgsl_need[RParams::SERVER]) {
                fwrite($lgsl_fp, "\xFF\xFF\xFF\xFF\x54Source Engine Query\x00" . (isset($lgsl_need['challenge']) ? $challenge_code : ""));
            } elseif ($lgsl_need[RParams::CONVARS]) {
                fwrite($lgsl_fp, "\xFF\xFF\xFF\xFF\x56{$challenge_code}");
            } elseif ($lgsl_need[RParams::PLAYERS]) {
                fwrite($lgsl_fp, "\xFF\xFF\xFF\xFF\x55{$challenge_code}");
            }
        }

        //---------------------------------------------------------+
        //  THE STANDARD HEADER POSITION REVEALS THE TYPE BUT IT MAY NOT ARRIVE FIRST
        //  ONCE WE KNOW THE TYPE WE CAN FIND THE TOTAL NUMBER OF PACKETS EXPECTED

        $packet_temp  = [];
        $packet_type  = 0;
        $packet_count = 0;
        $packet_total = 4;

        do {
            if (!($packet = fread($lgsl_fp, 4096))) {
                if ($lgsl_need[RParams::SERVER]) {
                    return false;
                } elseif ($lgsl_need[RParams::CONVARS]) {
                    $lgsl_need[RParams::CONVARS] = false;
                    return true;
                } else {
                    return true;
                }
            }

            //---------------------------------------------------------------------------------------------------------------------------------+
            // NEWER HL1 SERVERS REPLY TO A2S_INFO WITH 3 PACKETS ( HL1 FORMAT INFO, SOURCE FORMAT INFO, PLAYERS )
            // THIS DISCARDS UN-EXPECTED PACKET FORMATS ON THE GO ( AS READING IN ADVANCE CAUSES TIMEOUT DELAYS FOR OTHER SERVER VERSIONS )
            // ITS NOT PERFECT AS [s] CAN FLIP BETWEEN HL1 AND SOURCE FORMATS DEPENDING ON ARRIVAL ORDER ( MAYBE FIX WITH RETURN ON HL1 APPID )
            if($lgsl_need[RParams::SERVER]) {
                if($packet[4] == "D") {
                    continue;
                }
            } elseif($lgsl_need[RParams::CONVARS]) {
                if ($packet[4] == "m" || $packet[4] == "I" || $packet[4] == "D") {
                    continue;
                }
            } elseif ($lgsl_need[RParams::PLAYERS]) {
                if ($packet[4] == "m" || $packet[4] == "I") {
                    continue;
                }
            }
            //------------------------------------------------------------------------------------------------
            if(substr($packet, 0, 5) == "\xFF\xFF\xFF\xFF\x41") {
                $lgsl_need['challenge'] = substr($packet, 5, 4);
                $server[SParams::SERVER]['players'] = !$server[SParams::SERVER]['game'] ? -1 : $server[SParams::SERVER]['players'];
                return true;
            } // REPEAT WITH GIVEN CHALLENGE CODE
            elseif (substr($packet, 0, 4) == "\xFF\xFF\xFF\xFF") {
                $packet_total = 1;
                $packet_type = 1;
            } // SINGLE PACKET - HL1 OR HL2
            elseif (substr($packet, 9, 4) == "\xFF\xFF\xFF\xFF") {
                $packet_total = ord($packet[8]) & 0xF;
                $packet_type = 2;
            } // MULTI PACKET  - HL1 ( TOTAL IS LOWER NIBBLE OF BYTE )
            elseif (substr($packet, 12, 4) == "\xFF\xFF\xFF\xFF") {
                $packet_total = ord($packet[8]);
                $packet_type = 3;
            } // MULTI PACKET  - HL2
            elseif (substr($packet, 18, 2) == "BZ") {
                $packet_total = ord($packet[8]);
                $packet_type = 4;
            } // BZIP PACKET   - HL2

            $packet_count ++;
            $packet_temp[] = $packet;
        } while ($packet && $packet_count < $packet_total);

        if ($packet_type == 0) {
            return $server[SParams::SERVER] ? true : false;
        } // UNKNOWN RESPONSE ( SOME SERVERS ONLY SEND [s] )

        //---------------------------------------------------------+
        //  WITH THE TYPE WE CAN NOW SORT AND JOIN THE PACKETS IN THE CORRECT ORDER
        //  REMOVING ANY EXTRA HEADERS IN THE PROCESS

        $buffer = [];

        foreach ($packet_temp as $packet) {
            if($packet_type == 1) {
                $packet_order = 0;
            } elseif ($packet_type == 2) {
                $packet_order = ord($packet[8]) >> 4;
                $packet = substr($packet, 9);
            } // ( INDEX IS UPPER NIBBLE OF BYTE )
            elseif ($packet_type == 3) {
                $packet_order = ord($packet[9]);
                $packet = substr($packet, 12);
            } elseif ($packet_type == 4) {
                $packet_order = ord($packet[9]);
                $packet = substr($packet, 18);
            }

            $buffer[$packet_order] = $packet;
        }

        ksort($buffer);

        $buffer = implode("", $buffer);

        //---------------------------------------------------------+
        //  WITH THE PACKETS JOINED WE CAN NOW DECOMPRESS BZIP PACKETS
        //  THEN REMOVE THE STANDARD HEADER AND CHECK ITS CORRECT
        if ($packet_type == 4) {
            if (!function_exists("bzdecompress")) { // REQUIRES http://php.net/bzip2
                $server[SParams::CONVARS]['bzip2'] = "unavailable";
                $lgsl_need[RParams::CONVARS] = false;
                return true;
            }

            $buffer = bzdecompress($buffer);
        }

        $header = Byte::get($buffer, 4);

        if ($header != "\xFF\xFF\xFF\xFF") {
            return false;
        } // SOMETHING WENT WRONG

        //---------------------------------------------------------+

        $response_type = Byte::get($buffer, 1);

        if ($response_type == "I") { // SOURCE INFO ( HALF-LIFE 2 )
            $server['e']['netcode']     = ord(Byte::get($buffer, 1));

            $server[SParams::SERVER]['name']        = ParseString::get($buffer);
            $server[SParams::SERVER]['map']         = ParseString::get($buffer);
            $server[SParams::SERVER]['game']        = ParseString::get($buffer);

            $server[SParams::CONVARS]['description'] = ParseString::get($buffer);
            $server[SParams::CONVARS]['appid']       = Unpack::get(Byte::get($buffer, 2), "S");

            $server[SParams::SERVER]['players']     = ord(Byte::get($buffer, 1));
            $server[SParams::SERVER]['playersmax']  = ord(Byte::get($buffer, 1));

            $server[SParams::CONVARS]['bots']        = ord(Byte::get($buffer, 1));
            $server[SParams::CONVARS]['dedicated']   = Byte::get($buffer, 1);
            $server[SParams::CONVARS]['os']          = Byte::get($buffer, 1);

            $server[SParams::SERVER]['password']    = ord(Byte::get($buffer, 1));

            $server[SParams::CONVARS]['anticheat']   = ord(Byte::get($buffer, 1));
            $server[SParams::CONVARS]['version']     = ParseString::get($buffer);

            if (ord(Byte::get($buffer, 1)) == 177) {
                Byte::get($buffer, 10);
            } else {
                Byte::get($buffer, 6);
            }
            $server[SParams::CONVARS]['tags']        = ParseString::get($buffer);

            if ($server[SParams::SERVER]['game'] == 'rust') {
                preg_match('/cp\d{1,3}/', $server[SParams::CONVARS]['tags'], $e);
                $server[SParams::SERVER]['players'] = substr($e[0], 2);
                preg_match('/mp\d{1,3}/', $server[SParams::CONVARS]['tags'], $e);
                $server[SParams::SERVER]['playersmax'] = substr($e[0], 2);
            }
            if ($server[SParams::SERVER]['game'] == 'Y4YNzpz6Cuc=') { // EURO TRUCK SIMULATOR 2
                $server[SParams::SERVER]['game'] = 'Euro Truck Simulator 2';
                $server[SParams::SERVER]['map'] = substr($server[SParams::SERVER]['map'], 0, -4);
                if ($server[SParams::SERVER]['map'] == '/map/usa') {
                    $server[SParams::SERVER]['game'] = 'American Truck Simulator';
                }
            }
        } elseif ($response_type == "m") { // HALF-LIFE 1 INFO
            $server_ip                  = ParseString::get($buffer);
            $server[SParams::SERVER]['name']        = ParseString::get($buffer);
            $server[SParams::SERVER]['map']         = ParseString::get($buffer);
            $server[SParams::SERVER]['game']        = ParseString::get($buffer);

            $server[SParams::CONVARS]['description'] = ParseString::get($buffer);

            $server[SParams::SERVER]['players']     = ord(Byte::get($buffer, 1));
            $server[SParams::SERVER]['playersmax']  = ord(Byte::get($buffer, 1));

            $server[SParams::CONVARS]['netcode']     = ord(Byte::get($buffer, 1));
            $server[SParams::CONVARS]['dedicated']   = Byte::get($buffer, 1);
            $server[SParams::CONVARS]['os']          = Byte::get($buffer, 1);

            $server[SParams::SERVER]['password']    = ord(Byte::get($buffer, 1));

            if (ord(Byte::get($buffer, 1))) { // MOD FIELDS ( OFF FOR SOME HALFLIFEWON-VALVE SERVERS )
                $server[SParams::CONVARS]['mod_url_info']     = ParseString::get($buffer);
                $server[SParams::CONVARS]['mod_url_download'] = ParseString::get($buffer);
                $buffer = substr($buffer, 1);
                $server[SParams::CONVARS]['mod_version']      = Unpack::get(Byte::get($buffer, 4), "l");
                $server[SParams::CONVARS]['mod_size']         = Unpack::get(Byte::get($buffer, 4), "l");
                $server[SParams::CONVARS]['mod_server_side']  = ord(Byte::get($buffer, 1));
                $server[SParams::CONVARS]['mod_custom_dll']   = ord(Byte::get($buffer, 1));
            }

            $server[SParams::CONVARS]['anticheat'] = ord(Byte::get($buffer, 1));
            $server[SParams::CONVARS]['bots']      = ord(Byte::get($buffer, 1));
        } elseif ($response_type == "D") { // SOURCE AND HALF-LIFE 1 PLAYERS
            $returned = ord(Byte::get($buffer, 1));

            $player_key = 0;

            while ($buffer) {
                Byte::get($buffer, 1);
                $server[SParams::PLAYERS][$player_key]['name']  = ParseString::get($buffer);
                $server[SParams::PLAYERS][$player_key]['score'] = Unpack::get(Byte::get($buffer, 4), "l");
                $server[SParams::PLAYERS][$player_key]['time']  = Time::get(Unpack::get(Byte::get($buffer, 4), "f"));

                $player_key ++;
            }
        } elseif ($response_type == "E") { // SOURCE AND HALF-LIFE 1 RULES
            $returned = Unpack::get(Byte::get($buffer, 2), "S");

            while ($buffer) {
                $item_key   = strtolower(ParseString::get($buffer));
                $item_value = ParseString::get($buffer);

                $server[SParams::CONVARS][$item_key] = $item_value;
            }
        }

        //---------------------------------------------------------+

        // IF ONLY [s] WAS REQUESTED THEN REMOVE INCOMPLETE [e]
        if ($lgsl_need[RParams::SERVER] && !$lgsl_need[RParams::CONVARS]) {
            $server[SParams::CONVARS] = array();
        }

        if($lgsl_need[RParams::SERVER]) {
            $lgsl_need[RParams::SERVER] = false;
        } elseif ($lgsl_need[RParams::CONVARS]) {
            $lgsl_need[RParams::CONVARS] = false;
        } elseif ($lgsl_need[RParams::PLAYERS]) {
            $lgsl_need[RParams::PLAYERS] = false;
        }

        //---------------------------------------------------------+
        return true;
    }
}
