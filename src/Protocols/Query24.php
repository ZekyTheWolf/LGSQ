<?php

namespace ZekyWolf\LGSQ\Protocols;

use ZekyWolf\LGSQ\Helpers\{
    Parse\Byte,
    Parse\Unpack,
    Parse\ParseString,
    Parse\Time,
    EServerParams as SParams
};

class Query24
{
    public static function get(&$server, &$lgsl_need, &$lgsl_fp)
    {
        //---------------------------------------------------------+
        //  REFERENCE: http://cubelister.sourceforge.net

        fwrite($lgsl_fp, "\x21\x21");

        $buffer = fread($lgsl_fp, 4096);

        if (!$buffer) {
            return false;
        }

        $buffer = substr($buffer, 2); // REMOVE HEADER

        //---------------------------------------------------------+

        if ($buffer[0] == "\x1b") { // CUBE 1
            // RESPONSE IS XOR ENCODED FOR SOME STRANGE REASON
            for ($i=0; $i<strlen($buffer); $i++) {
                $buffer[$i] = chr(ord($buffer[$i]) ^ 0x61);
            }

            $server[SParams::SERVER]['game']       = "Cube";
            $server[SParams::CONVARS]['netcode']    = ord(Byte::get($buffer, 1));
            $server[SParams::CONVARS]['gamemode']   = ord(Byte::get($buffer, 1));
            $server[SParams::SERVER]['players']    = ord(Byte::get($buffer, 1));
            $server[SParams::CONVARS]['timeleft']   = Time::get(ord(Byte::get($buffer, 1)) * 60);
            $server[SParams::SERVER]['map']        = ParseString::get($buffer);
            $server[SParams::SERVER]['name']       = ParseString::get($buffer);
            $server[SParams::SERVER]['playersmax'] = "0"; // NOT PROVIDED

            // DOES NOT RETURN PLAYER INFORMATION

            return true;
        } elseif ($buffer[0] == "\x80") { // ASSAULT CUBE
            $server[SParams::SERVER]['game']       = "AssaultCube";
            $server[SParams::CONVARS]['netcode']    = ord(Byte::get($buffer, 1));
            $server[SParams::CONVARS]['version']    = Unpack::get(Byte::get($buffer, 2), "S");
            $server[SParams::CONVARS]['gamemode']   = ord(Byte::get($buffer, 1));
            $server[SParams::SERVER]['players']    = ord(Byte::get($buffer, 1));
            $server[SParams::CONVARS]['timeleft']   = Time::get(ord(Byte::get($buffer, 1)) * 60);
            $server[SParams::SERVER]['map']        = ParseString::get($buffer);
            $server[SParams::SERVER]['name']       = ParseString::get($buffer);
            $server[SParams::SERVER]['playersmax'] = ord(Byte::get($buffer, 1));

        } elseif ($buffer[1] == "\x05") { // CUBE 2 - SAUERBRATEN
            $server[SParams::SERVER]['game']       = "Sauerbraten";
            $server[SParams::SERVER]['players']    = ord(Byte::get($buffer, 1));
            $info_returned             = ord(Byte::get($buffer, 1)); // CODED FOR 5
            $server[SParams::CONVARS]['netcode']    = ord(Byte::get($buffer, 1));
            $server[SParams::CONVARS]['version']    = Unpack::get(Byte::get($buffer, 2), "S");
            $server[SParams::CONVARS]['gamemode']   = ord(Byte::get($buffer, 1));
            $server[SParams::CONVARS]['timeleft']   = Time::get(ord(Byte::get($buffer, 1)) * 60);
            $server[SParams::SERVER]['playersmax'] = ord(Byte::get($buffer, 1));
            $server[SParams::SERVER]['password']   = ord(Byte::get($buffer, 1)); // BIT FIELD
            $server[SParams::SERVER]['password']   = $server[SParams::SERVER]['password'] & 4 ? "1" : "0";
            $server[SParams::SERVER]['map']        = ParseString::get($buffer);
            $server[SParams::SERVER]['name']       = ParseString::get($buffer);
        } elseif ($buffer[1] == "\x06") { // BLOODFRONTIER
            $server[SParams::SERVER]['game']       = "Blood Frontier";
            $server[SParams::SERVER]['players']    = ord(Byte::get($buffer, 1));
            $info_returned             = ord(Byte::get($buffer, 1)); // CODED FOR 6
            $server[SParams::CONVARS]['netcode']    = ord(Byte::get($buffer, 1));
            $server[SParams::CONVARS]['version']    = Unpack::get(Byte::get($buffer, 2), "S");
            $server[SParams::CONVARS]['gamemode']   = ord(Byte::get($buffer, 1));
            $server[SParams::CONVARS]['mutators']   = ord(Byte::get($buffer, 1));
            $server[SParams::CONVARS]['timeleft']   = Time::get(ord(Byte::get($buffer, 1)) * 60);
            $server[SParams::SERVER]['playersmax'] = ord(Byte::get($buffer, 1));
            $server[SParams::SERVER]['password']   = ord(Byte::get($buffer, 1)); // BIT FIELD
            $server[SParams::SERVER]['password']   = $server[SParams::SERVER]['password'] & 4 ? "1" : "0";
            $server[SParams::SERVER]['map']        = ParseString::get($buffer);
            $server[SParams::SERVER]['name']       = ParseString::get($buffer);
        } else { // UNKNOWN
            return false;
        }

        //---------------------------------------------------------+
        //  CRAZY PROTOCOL - REQUESTS MUST BE MADE FOR EACH PLAYER
        //  BOTS ARE RETURNED BUT NOT INCLUDED IN THE PLAYER TOTAL
        //  AND THERE CAN BE ID GAPS BETWEEN THE PLAYERS RETURNED

        if ($lgsl_need[SParams::PLAYERS] && $server[SParams::SERVER]['players']) {
            $player_key = 0;

            for ($player_id=0; $player_id<32; $player_id++) {
                fwrite($lgsl_fp, "\x00\x01".chr($player_id));

                // READ PACKET
                $buffer = fread($lgsl_fp, 4096);
                if (!$buffer) {
                    break;
                }

                // CHECK IF PLAYER ID IS ACTIVE
                if ($buffer[5] != "\x00") {
                    if ($player_key < $server[SParams::SERVER]['players']) {
                        continue;
                    }
                    break;
                }

                // IF PREVIEW PACKET GET THE FULL PACKET THAT FOLLOWS
                if (strlen($buffer) < 15) {
                    $buffer = fread($lgsl_fp, 4096);
                    if (!$buffer) {
                        break;
                    }
                }

                // REMOVE HEADER
                $buffer = substr($buffer, 7);

                // WE CAN NOW GET THE PLAYER DETAILS
                if ($server[SParams::SERVER]['game'] == "Blood Frontier") {
                    $server[SParams::PLAYERS][$player_key]['pid']       = Unpack::get(Byte::get($buffer, 1), "C");
                    $server[SParams::PLAYERS][$player_key]['ping']      = Unpack::get(Byte::get($buffer, 1), "C");
                    $server[SParams::PLAYERS][$player_key]['ping']      = $server[SParams::PLAYERS][$player_key]['ping'] == 128 ? Unpack::get(Byte::get($buffer, 2), "S") : $server[SParams::PLAYERS][$player_key]['ping'];
                    $server[SParams::PLAYERS][$player_key]['name']      = ParseString::get($buffer);
                    $server[SParams::PLAYERS][$player_key]['team']      = ParseString::get($buffer);
                    $server[SParams::PLAYERS][$player_key]['score']     = Unpack::get(Byte::get($buffer, 1), "c");
                    $server[SParams::PLAYERS][$player_key]['damage']    = Unpack::get(Byte::get($buffer, 1), "C");
                    $server[SParams::PLAYERS][$player_key]['deaths']    = Unpack::get(Byte::get($buffer, 1), "C");
                    $server[SParams::PLAYERS][$player_key]['teamkills'] = Unpack::get(Byte::get($buffer, 1), "C");
                    $server[SParams::PLAYERS][$player_key]['accuracy']  = Unpack::get(Byte::get($buffer, 1), "C")."%";
                    $server[SParams::PLAYERS][$player_key]['health']    = Unpack::get(Byte::get($buffer, 1), "c");
                    $server[SParams::PLAYERS][$player_key]['spree']     = Unpack::get(Byte::get($buffer, 1), "C");
                    $server[SParams::PLAYERS][$player_key]['weapon']    = Unpack::get(Byte::get($buffer, 1), "C");
                } else {
                    $server[SParams::PLAYERS][$player_key]['pid']       = Unpack::get(Byte::get($buffer, 1), "C");
                    $server[SParams::PLAYERS][$player_key]['name']      = ParseString::get($buffer);
                    $server[SParams::PLAYERS][$player_key]['team']      = ParseString::get($buffer);
                    $server[SParams::PLAYERS][$player_key]['score']     = Unpack::get(Byte::get($buffer, 1), "c");
                    $server[SParams::PLAYERS][$player_key]['deaths']    = Unpack::get(Byte::get($buffer, 1), "C");
                    $server[SParams::PLAYERS][$player_key]['teamkills'] = Unpack::get(Byte::get($buffer, 1), "C");
                    $server[SParams::PLAYERS][$player_key]['accuracy']  = Unpack::get(Byte::get($buffer, 1), "C")."%";
                    $server[SParams::PLAYERS][$player_key]['health']    = Unpack::get(Byte::get($buffer, 1), "c");
                    $server[SParams::PLAYERS][$player_key]['armour']    = Unpack::get(Byte::get($buffer, 1), "C");
                    $server[SParams::PLAYERS][$player_key]['weapon']    = Unpack::get(Byte::get($buffer, 1), "C");
                }

                $player_key++;
            }
        }

        //---------------------------------------------------------+

        return true;
    }
}
