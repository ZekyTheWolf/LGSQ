<?php

namespace ZekyWolf\LGSQ\Protocols;

use ZekyWolf\LGSQ\Helpers\{
    Parse\Pascal,
    EServerParams as SParams,
};

class Query39
{
    public static function get(&$server, &$lgsl_need, &$lgsl_fp) // Mafia 2: MP
    {
        fwrite($lgsl_fp, "M2MPi");
        $buffer = fread($lgsl_fp, 1024);

        if (!$buffer) {
            return false;
        }

        $buffer = substr($buffer, 4); // REMOVE HEADER

        $server[SParams::SERVER]['name']        = Pascal::get($buffer, 1, -1);
        $server[SParams::SERVER]['map']         = "Empire Bay";
        $server[SParams::SERVER]['players']     = Pascal::get($buffer, 1, -1);
        $server[SParams::SERVER]['playersmax']  = Pascal::get($buffer, 1, -1);
        $server[SParams::SERVER]['password']    = 0;
        $server[SParams::CONVARS]['gamemode']    = Pascal::get($buffer, 1, -1);

        return true;
    }
}
