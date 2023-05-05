<?php

namespace ZekyWolf\LGSQ\Protocols;

use ZekyWolf\LGSQ\Helpers\{
    Parse\Byte,
    Parse\Unpack,
    Parse\ParseString,
    EServerParams as SParams,
};

class Query14
{
    public static function get(&$server, &$lgsl_need, &$lgsl_fp)
    {
        //---------------------------------------------------------+
        //  REFERENCE: http://flstat.cryosphere.co.uk/global-list.php

        fwrite($lgsl_fp, "\x00\x02\xf1\x26\x01\x26\xf0\x90\xa6\xf0\x26\x57\x4e\xac\xa0\xec\xf8\x68\xe4\x8d\x21");

        $buffer = fread($lgsl_fp, 4096);

        if (!$buffer) {
            return false;
        }

        //---------------------------------------------------------+

        $buffer = substr($buffer, 4); // HEADER   ( 00 03 F1 26 )
        $buffer = substr($buffer, 4); // NOT USED ( 87 + NAME LENGTH )
        $buffer = substr($buffer, 4); // NOT USED ( NAME END TO BUFFER END LENGTH )
        $buffer = substr($buffer, 4); // UNKNOWN  ( 80 )

        $server[SParams::SERVER]['map']        = "freelancer";
        $server[SParams::SERVER]['password']   = Unpack::get(Byte::get($buffer, 4), "l") - 1 ? 1 : 0;
        $server[SParams::SERVER]['playersmax'] = Unpack::get(Byte::get($buffer, 4), "l") - 1;
        $server[SParams::SERVER]['players']    = Unpack::get(Byte::get($buffer, 4), "l") - 1;
        $buffer                    = substr($buffer, 4);  // UNKNOWN ( 88 )
        $name_length               = Unpack::get(Byte::get($buffer, 4), "l");
        $buffer                    = substr($buffer, 56); // UNKNOWN
        $server[SParams::SERVER]['name']       = Byte::get($buffer, $name_length);

        ParseString::get($buffer, 0, ":");
        ParseString::get($buffer, 0, ":");
        ParseString::get($buffer, 0, ":");
        ParseString::get($buffer, 0, ":");
        ParseString::get($buffer, 0, ":");

        // WHATS LEFT IS THE MOTD
        $server[SParams::CONVARS]['motd'] = substr($buffer, 0, -1);

        // REMOVE UTF-8 ENCODING NULLS
        $server[SParams::SERVER]['name'] = str_replace("\x00", "", $server[SParams::SERVER]['name']);
        $server[SParams::CONVARS]['motd'] = str_replace("\x00", "", $server[SParams::CONVARS]['motd']);

        // DOES NOT RETURN PLAYER INFORMATION

        //---------------------------------------------------------+

        return true;
    }
}
