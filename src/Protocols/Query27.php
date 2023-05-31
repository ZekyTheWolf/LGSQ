<?php

namespace ZekyWolf\LGSQ\Protocols;

use ZekyWolf\LGSQ\{
    Helpers\Parse\Byte,
    Helpers\Parse\Colors,
    Helpers\Parse\Unpack,
    Helpers\Parse\ParseString,
    Helpers\Parse\Time,
    Params\EServerParams as SParams,
    Params\EConnectionParams as CParams
};

class Query27
{
    public static function get(&$server, &$lgsl_need, &$lgsl_fp)
    {
        //---------------------------------------------------------+
        //  REFERENCE:
        //  http://skulltag.com/wiki/Launcher_protocol
        //  http://en.wikipedia.org/wiki/Huffman_coding
        //  http://www.greycube.com/help/lgsl_other/skulltag_huffman.txt

        $huffman_table = [
            "010","110111","101110010","00100","10011011","00101","100110101","100001100","100101100","001110100","011001001","11001000","101100001","100100111","001111111","101110000","101110001","001111011",
            "11011011","101111100","100001110","110011111","101100000","001111100","0011000","001111000","10001100","100101011","100010000","101111011","100100110","100110010","0111","1111000","00010001",
            "00011010","00011000","00010101","00010000","00110111","00110110","00011100","01100101","1101001","00110100","10110011","10110100","1111011","10111100","10111010","11001001","11010101","11111110",
            "11111100","10001110","11110011","001101011","10000000","000101101","11010000","001110111","100000010","11100111","001100101","11100110","00111001","10001010","00010011","001110110","10001111",
            "000111110","11000111","11010111","11100011","000101000","001100111","11010100","000111010","10010111","100000111","000100100","001110001","11111010","100100011","11110100","000110111","001111010",
            "100010011","100110001","11101","110001011","101110110","101111110","100100010","100101001","01101","100100100","101100101","110100011","100111100","110110001","100010010","101101101","011001110",
            "011001101","11111101","100010001","100110000","110001000","110110000","0001001010","110001010","101101010","000110110","10110001","110001101","110101101","110001100","000111111","110010101",
            "111000100","11011001","110010110","110011110","000101100","001110101","101111101","1001110","0000","1000010","0001110111","0001100101","1010","11001110","0110011000","0110011001","1000011011",
            "1001100110","0011110011","0011001100","11111001","0110010001","0001010011","1000011010","0001001011","1001101001","101110111","1000001101","1000011111","1100000101","0110000000","1011011101",
            "11110101","0001111011","1101000101","1101000100","1001000010","0110000001","1011001000","100101010","1100110","111100101","1100101111","0001100111","1110000","0011111100","11111011","1100101110",
            "101110011","1001100111","1001111111","1011011100","111110001","101111010","1011010110","1001010000","1001000011","1001111110","0011111011","1000011110","1000101100","01100001","00010111",
            "1000000110","110000101","0001111010","0011001101","0110011110","110010100","111000101","0011001001","0011110010","110000001","101101111","0011111101","110110100","11100100","1011001001",
            "0011001000","0001110110","111111111","110101100","111111110","1000001011","1001011010","110000000","000111100","111110000","011000001","1001111010","111001011","011000111","1001000001",
            "1001111100","1000110111","1001101000","0110001100","1001111011","0011010101","1000101101","0011111010","0001100100","01100010","110000100","101101100","0110011111","1001011011","1000101110",
            "111100100","1000110110","0110001101","1001000000","110110101","1000001000","1000001001","1100000100","110001001","1000000111","1001111101","111001010","0011010100","1000101111","101111111",
            "0001010010","0011100000","0001100110","1000001010","0011100001","11000011","1011010111","1000001100","100011010","0110010000","100100101","1001010001","110000011"
        ];

        //---------------------------------------------------------+

        fwrite($lgsl_fp, "\x02\xB8\x49\x1A\x9C\x8B\xB5\x3F\x1E\x8F\x07");

        $packet = fread($lgsl_fp, 4096);

        if (!$packet) {
            return false;
        }

        $packet = substr($packet, 1); // REMOVE HEADER

        //---------------------------------------------------------+

        $packet_binary = "";

        for ($i=0; $i<strlen($packet); $i++) {
            $packet_binary .= strrev(sprintf("%08b", ord($packet[$i])));
        }

        $buffer = "";

        while ($packet_binary) {
            foreach ($huffman_table as $ascii => $huffman_binary) {
                $huffman_length = strlen($huffman_binary);

                if (substr($packet_binary, 0, $huffman_length) === $huffman_binary) {
                    $packet_binary = substr($packet_binary, $huffman_length);
                    $buffer .= chr($ascii);
                    continue 2;
                }
            }
            break;
        }

        //---------------------------------------------------------+

        $response_status        = Unpack::get(Byte::get($buffer, 4), "l");
        if ($response_status != "5660023") {
            return false;
        }
        $response_time          = Unpack::get(Byte::get($buffer, 4), "l");
        $server[SParams::CONVARS]['version'] = ParseString::get($buffer);
        $response_flag          = Unpack::get(Byte::get($buffer, 4), "l");

        //---------------------------------------------------------+

        if ($response_flag & 0x00000001) {
            $server[SParams::SERVER]['name']       = ParseString::get($buffer);
        }
        if ($response_flag & 0x00000002) {
            $server[SParams::CONVARS]['wadurl']     = ParseString::get($buffer);
        }
        if ($response_flag & 0x00000004) {
            $server[SParams::CONVARS]['email']      = ParseString::get($buffer);
        }
        if ($response_flag & 0x00000008) {
            $server[SParams::SERVER]['map']        = ParseString::get($buffer);
        }
        if ($response_flag & 0x00000010) {
            $server[SParams::SERVER]['playersmax'] = ord(Byte::get($buffer, 1));
        }
        if ($response_flag & 0x00000020) {
            $server[SParams::CONVARS]['playersmax'] = ord(Byte::get($buffer, 1));
        }
        if ($response_flag & 0x00000040) {
            $pwad_total = ord(Byte::get($buffer, 1));

            $server[SParams::CONVARS]['pwads'] = "";

            for ($i=0; $i<$pwad_total; $i++) {
                $server[SParams::CONVARS]['pwads'] .= ParseString::get($buffer)." ";
            }
        }
        if ($response_flag & 0x00000080) {
            $server[SParams::CONVARS]['gametype'] = ord(Byte::get($buffer, 1));
            $server[SParams::CONVARS]['instagib'] = ord(Byte::get($buffer, 1));
            $server[SParams::CONVARS]['buckshot'] = ord(Byte::get($buffer, 1));
        }
        if ($response_flag & 0x00000100) {
            $server[SParams::SERVER]['game']         = ParseString::get($buffer);
        }
        if ($response_flag & 0x00000200) {
            $server[SParams::CONVARS]['iwad']         = ParseString::get($buffer);
        }
        if ($response_flag & 0x00000400) {
            $server[SParams::SERVER]['password']     = ord(Byte::get($buffer, 1));
        }
        if ($response_flag & 0x00000800) {
            $server[SParams::CONVARS]['playpassword'] = ord(Byte::get($buffer, 1));
        }
        if ($response_flag & 0x00001000) {
            $server[SParams::CONVARS]['skill']        = ord(Byte::get($buffer, 1)) + 1;
        }
        if ($response_flag & 0x00002000) {
            $server[SParams::CONVARS]['botskill']     = ord(Byte::get($buffer, 1)) + 1;
        }
        if ($response_flag & 0x00004000) {
            $server[SParams::CONVARS]['dmflags']     = Unpack::get(Byte::get($buffer, 4), "l");
            $server[SParams::CONVARS]['dmflags2']    = Unpack::get(Byte::get($buffer, 4), "l");
            $server[SParams::CONVARS]['compatflags'] = Unpack::get(Byte::get($buffer, 4), "l");
        }
        if ($response_flag & 0x00010000) {
            $server[SParams::CONVARS]['fraglimit'] = Unpack::get(Byte::get($buffer, 2), "s");
            $timelimit                = Unpack::get(Byte::get($buffer, 2), "S");

            if ($timelimit) { // FUTURE VERSION MAY ALWAYS RETURN THIS
                $server[SParams::CONVARS]['timeleft'] = Time::get(Unpack::get(Byte::get($buffer, 2), "S") * 60);
            }

            $server[SParams::CONVARS]['timelimit']  = Time::get($timelimit * 60);
            $server[SParams::CONVARS]['duellimit']  = Unpack::get(Byte::get($buffer, 2), "s");
            $server[SParams::CONVARS]['pointlimit'] = Unpack::get(Byte::get($buffer, 2), "s");
            $server[SParams::CONVARS]['winlimit']   = Unpack::get(Byte::get($buffer, 2), "s");
        }
        if ($response_flag & 0x00020000) {
            $server[SParams::CONVARS]['teamdamage'] = Unpack::get(Byte::get($buffer, 4), "f");
        }
        if ($response_flag & 0x00040000) { // DEPRECIATED
            $server[SParams::TEAMS][0]['score'] = Unpack::get(Byte::get($buffer, 2), "s");
            $server[SParams::TEAMS][1]['score'] = Unpack::get(Byte::get($buffer, 2), "s");
        }
        if ($response_flag & 0x00080000) {
            $server[SParams::SERVER]['players'] = ord(Byte::get($buffer, 1));
        }
        if ($response_flag & 0x00100000) {
            for ($i=0; $i<$server[SParams::SERVER]['players']; $i++) {
                $server[SParams::PLAYERS][$i]['name']      = Colors::get(ParseString::get($buffer), $server[SParams::BASIC][CParams::TYPE]);
                $server[SParams::PLAYERS][$i]['score']     = Unpack::get(Byte::get($buffer, 2), "s");
                $server[SParams::PLAYERS][$i]['ping']      = Unpack::get(Byte::get($buffer, 2), "S");
                $server[SParams::PLAYERS][$i]['spectator'] = ord(Byte::get($buffer, 1));
                $server[SParams::PLAYERS][$i]['bot']       = ord(Byte::get($buffer, 1));

                if (($response_flag & 0x00200000) && ($response_flag & 0x00400000)) {
                    $server[SParams::PLAYERS][$i]['team'] = ord(Byte::get($buffer, 1));
                }

                $server[SParams::PLAYERS][$i]['time'] = Time::get(ord(Byte::get($buffer, 1)) * 60);
            }
        }
        if ($response_flag & 0x00200000) {
            $team_total = ord(Byte::get($buffer, 1));

            if ($response_flag & 0x00400000) {
                for ($i=0; $i<$team_total; $i++) {
                    $server[SParams::TEAMS][$i]['name'] = ParseString::get($buffer);
                }
            }
            if ($response_flag & 0x00800000) {
                for ($i=0; $i<$team_total; $i++) {
                    $server[SParams::TEAMS][$i]['color'] = Unpack::get(Byte::get($buffer, 4), "l");
                }
            }
            if ($response_flag & 0x01000000) {
                for ($i=0; $i<$team_total; $i++) {
                    $server[SParams::TEAMS][$i]['score'] = Unpack::get(Byte::get($buffer, 2), "s");
                }
            }

            for ($i=0; $i<$server[SParams::SERVER]['players']; $i++) {
                if ($server[SParams::TEAMS][$i]['name']) {
                    $server[SParams::PLAYERS][$i]['team'] = $server[SParams::TEAMS][$i]['name'];
                }
            }
        }

        //---------------------------------------------------------+

        return true;
    }
}
