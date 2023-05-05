<?php

namespace ZekyWolf\LGSQ\Protocols;

use ZekyWolf\LGSQ\Helpers\{
    Parse\Byte,
    Parse\Unpack,
    Parse\ParseString,
    Parse\Time,
    EServerParams as SParams
};

class Query28
{
    public static function get(&$server, &$lgsl_need, &$lgsl_fp)
    {
        //---------------------------------------------------------+
        //  REFERENCE: http://doomutils.ucoz.com

        fwrite($lgsl_fp, "\xA3\xDB\x0B\x00"."\xFC\xFD\xFE\xFF"."\x01\x00\x00\x00"."\x21\x21\x21\x21");

        $buffer = fread($lgsl_fp, 4096);

        if (!$buffer) {
            return false;
        }

        //---------------------------------------------------------+

        $response_status  = Unpack::get(Byte::get($buffer, 4), "l");
        if ($response_status != "5560022") {
            return false;
        }
        $response_version = Unpack::get(Byte::get($buffer, 4), "l");
        $response_time    = Unpack::get(Byte::get($buffer, 4), "l");

        $server[SParams::CONVARS]['invited']    = ord(Byte::get($buffer, 1));
        $server[SParams::CONVARS]['version']    = Unpack::get(Byte::get($buffer, 2), "S");
        $server[SParams::SERVER]['name']       = ParseString::get($buffer);
        $server[SParams::SERVER]['players']    = ord(Byte::get($buffer, 1));
        $server[SParams::SERVER]['playersmax'] = ord(Byte::get($buffer, 1));
        $server[SParams::SERVER]['map']        = ParseString::get($buffer);

        $pwad_total = ord(Byte::get($buffer, 1));

        for ($i=0; $i<$pwad_total; $i++) {
            $server[SParams::CONVARS]['pwads'] .= ParseString::get($buffer)." ";
            $pwad_optional         = ord(Byte::get($buffer, 1));
            $pwad_alternative      = ParseString::get($buffer);
        }

        $server[SParams::CONVARS]['gametype']   = ord(Byte::get($buffer, 1));
        $server[SParams::SERVER]['game']       = ParseString::get($buffer);
        $server[SParams::CONVARS]['iwad']       = ParseString::get($buffer);
        $iwad_altenative           = ParseString::get($buffer);
        $server[SParams::CONVARS]['skill']      = ord(Byte::get($buffer, 1)) + 1;
        $server[SParams::CONVARS]['wadurl']     = ParseString::get($buffer);
        $server[SParams::CONVARS]['email']      = ParseString::get($buffer);
        $server[SParams::CONVARS]['dmflags']    = Unpack::get(Byte::get($buffer, 4), "l");
        $server[SParams::CONVARS]['dmflags2']   = Unpack::get(Byte::get($buffer, 4), "l");
        $server[SParams::SERVER]['password']   = ord(Byte::get($buffer, 1));
        $server[SParams::CONVARS]['inviteonly'] = ord(Byte::get($buffer, 1));
        $server[SParams::CONVARS]['players']    = ord(Byte::get($buffer, 1));
        $server[SParams::CONVARS]['playersmax'] = ord(Byte::get($buffer, 1));
        $server[SParams::CONVARS]['timelimit']  = Time::get(Unpack::get(Byte::get($buffer, 2), "S") * 60);
        $server[SParams::CONVARS]['timeleft']   = Time::get(Unpack::get(Byte::get($buffer, 2), "S") * 60);
        $server[SParams::CONVARS]['fraglimit']  = Unpack::get(Byte::get($buffer, 2), "s");
        $server[SParams::CONVARS]['gravity']    = Unpack::get(Byte::get($buffer, 4), "f");
        $server[SParams::CONVARS]['aircontrol'] = Unpack::get(Byte::get($buffer, 4), "f");
        $server[SParams::CONVARS]['playersmin'] = ord(Byte::get($buffer, 1));
        $server[SParams::CONVARS]['removebots'] = ord(Byte::get($buffer, 1));
        $server[SParams::CONVARS]['voting']     = ord(Byte::get($buffer, 1));
        $server[SParams::CONVARS]['serverinfo'] = ParseString::get($buffer);
        $server[SParams::CONVARS]['startup']    = Unpack::get(Byte::get($buffer, 4), "l");

        for ($i=0; $i<$server[SParams::SERVER]['players']; $i++) {
            $server[SParams::PLAYERS][$i]['name']      = ParseString::get($buffer);
            $server[SParams::PLAYERS][$i]['score']     = Unpack::get(Byte::get($buffer, 2), "s");
            $server[SParams::PLAYERS][$i]['death']     = Unpack::get(Byte::get($buffer, 2), "s");
            $server[SParams::PLAYERS][$i]['ping']      = Unpack::get(Byte::get($buffer, 2), "S");
            $server[SParams::PLAYERS][$i]['time']      = Time::get(Unpack::get(Byte::get($buffer, 2), "S") * 60);
            $server[SParams::PLAYERS][$i]['bot']       = ord(Byte::get($buffer, 1));
            $server[SParams::PLAYERS][$i]['spectator'] = ord(Byte::get($buffer, 1));
            $server[SParams::PLAYERS][$i]['team']      = ord(Byte::get($buffer, 1));
            $server[SParams::PLAYERS][$i]['country']   = Byte::get($buffer, 2);
        }

        $team_total                = ord(Byte::get($buffer, 1));
        $server[SParams::CONVARS]['pointlimit'] = Unpack::get(Byte::get($buffer, 2), "s");
        $server[SParams::CONVARS]['teamdamage'] = Unpack::get(Byte::get($buffer, 4), "f");

        for ($i=0; $i<$team_total; $i++) { // RETURNS 4 TEAMS BUT IGNORE THOSE NOT IN USE
            $server[SParams::TEAMS]['team'][$i]['name']  = ParseString::get($buffer);
            $server[SParams::TEAMS]['team'][$i]['color'] = Unpack::get(Byte::get($buffer, 4), "l");
            $server[SParams::TEAMS]['team'][$i]['score'] = Unpack::get(Byte::get($buffer, 2), "s");
        }

        for ($i=0; $i<$server[SParams::SERVER]['players']; $i++) {
            if ($server[SParams::TEAMS][$i]['name']) {
                $server[SParams::PLAYERS][$i]['team'] = $server[SParams::TEAMS][$i]['name'];
            }
        }

        //---------------------------------------------------------+

        return true;
    }
}
