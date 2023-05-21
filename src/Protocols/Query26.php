<?php

namespace ZekyWolf\LGSQ\Protocols;

use ZekyWolf\LGSQ\Helpers\{
    Parse\Byte,
    Parse\Pascal,
    Parse\Unpack,
    Parse\Crypt,
    EServerParams as SParams,
    EConnectionParams as CParams
};

class Query26
{
    public static function getlgsl_query_26(&$server, &$lgsl_need, &$lgsl_fp)
    {
        //---------------------------------------------------------+
        //  REFERENCE:
        //  http://hazardaaclan.com/wiki/doku.php?id=aa3_server_query
        //  http://aluigi.altervista.org/papers.htm#aa3authdec

        if (!function_exists('gzuncompress')) {
            return false;
        } // REQUIRES http://www.php.net/zlib

        $packet = "\x0A\x00playerName\x06\x06\x00query\x00";
        Crypt::get($server[SParams::BASIC][CParams::TYPE], $packet, true);
        fwrite($lgsl_fp, "\x4A\x35\xFF\xFF\x02\x00\x02\x00\x01\x00{$packet}");

        $buffer = array();
        $packet_count = 0;
        $packet_total = 4;

        do {
            $packet_count ++;
            $packet = fread($lgsl_fp, 4096);

            if (!isset($packet[5])) {
                return false;
            }

            if ($packet[5] == "\x03") { // MULTI PACKET
                $packet_order = ord($packet[10]);
                $packet_total = ord($packet[12]);
                $packet = substr($packet, 14);
                $buffer[$packet_order] = $packet;
            } elseif ($packet[5] == "\x02") { // SINGLE PACKET
                $buffer[0] = substr($packet, 10);
                break;
            } else {
                return false;
            }
        } while ($packet_count < $packet_total);

        //---------------------------------------------------------+

        ksort($buffer);

        $buffer = implode("", $buffer);

        Crypt::get($server[SParams::BASIC][CParams::TYPE], $buffer, false);

        $buffer = @gzuncompress($buffer);

        if (!$buffer) {
            return false;
        }

        //---------------------------------------------------------+

        $raw = array();

        do {
            $raw_name = Pascal::get($buffer, 2);
            $raw_type = Byte::get($buffer, 1);

            switch ($raw_type) {
                // SINGLE INTEGER
                case "\x02":
                    $raw[$raw_name] = Unpack::get(Byte::get($buffer, 4), "i");
                    break;

                    // ARRAY OF STRINGS
                case "\x07":
                    $raw_total = Unpack::get(Byte::get($buffer, 2), "S");

                    for ($i=0; $i<$raw_total;$i++) {
                        $raw_value = Pascal::get($buffer, 2);
                        if (substr($raw_value, -1) == "\x00") {
                            $raw_value = substr($raw_value, 0, -1);
                        } // SOME STRINGS HAVE NULLS
                        $raw[$raw_name][] = $raw_value;
                    }
                    break;

                    // 01=BOOLEAN|03=SHORT INTEGER|04=DOUBLE
                    // 05=CHAR|06=STRING|09=ARRAY OF INTEGERS
                default:
                    break 2;
            }
        } while ($buffer);

        if (!isset($raw['attributeNames'])  || !is_array($raw['attributeNames'])) {
            return false;
        }
        if (!isset($raw['attributeValues']) || !is_array($raw['attributeValues'])) {
            return false;
        }

        //---------------------------------------------------------+

        foreach ($raw['attributeNames'] as $key => $field) {
            $field = strtolower($field);

            preg_match("/^player(.*)(\d+)$/U", $field, $match);

            if (isset($match[1])) {
                // IGNORE POINTLESS PLAYER FIELDS
                if ($match[1] == "mapname") {
                    continue;
                }
                if ($match[1] == "version") {
                    continue;
                }
                if ($match[1] == "servermapname") {
                    continue;
                }
                if ($match[1] == "serveripaddress") {
                    continue;
                }

                // LGSL STANDARD ( SWAP NAME AS ITS ACTUALLY THE ACCOUNT NAME )
                if ($match[1] == "name") {
                    $match[1] = "username";
                }
                if ($match[1] == "soldiername") {
                    $match[1] = "name";
                }

                $server[SParams::PLAYERS][$match[2]][$match[1]] = $raw['attributeValues'][$key];
            } else {
                if (substr($field, 0, 6) == "server") {
                    $field = substr($field, 6);
                }
                $server[SParams::CONVARS][$field] = $raw['attributeValues'][$key];
            }
        }

        $lgsl_conversion = array("gamename"=>"name","mapname"=>"map","playercount"=>"players","maxplayers"=>"playersmax","flagpassword"=>"password");
        foreach ($lgsl_conversion as $e => $s) {
            $server[SParams::SERVER][$s] = $server[SParams::CONVARS][$e];
            unset($server['ea'][$e]);
        } // LGSL STANDARD
        $server[SParams::SERVER]['playersmax'] += intval($server[SParams::CONVARS]['maxspectators']); // ADD SPECTATOR SLOTS TO MAX PLAYERS

        //---------------------------------------------------------+

        return true;
    }
}
