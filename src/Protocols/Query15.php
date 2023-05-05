<?php

namespace ZekyWolf\LGSQ\Protocols;

use ZekyWolf\LGSQ\Helpers\EServerParams as SParams;

class Query15
{
    public static function get(&$server, &$lgsl_need, &$lgsl_fp)
    {
        //---------------------------------------------------------+

        fwrite($lgsl_fp, "GTR2_Direct_IP_Search\x00");

        $buffer = fread($lgsl_fp, 4096);

        if (!$buffer) {
            return false;
        }

        //---------------------------------------------------------+

        $buffer = str_replace("\xFE", "\xFF", $buffer);
        $buffer = explode("\xFF", $buffer);

        $server[SParams::SERVER]['name']       = $buffer[3];
        $server[SParams::SERVER]['game']       = $buffer[7];
        $server[SParams::CONVARS]['version']    = $buffer[11];
        $server[SParams::CONVARS]['hostport']   = $buffer[15];
        $server[SParams::SERVER]['map']        = $buffer[19];
        $server[SParams::SERVER]['players']    = $buffer[25];
        $server[SParams::SERVER]['playersmax'] = $buffer[27];
        $server[SParams::CONVARS]['gamemode']   = $buffer[31];

        // DOES NOT RETURN PLAYER INFORMATION

        //---------------------------------------------------------+

        return true;
    }
}
