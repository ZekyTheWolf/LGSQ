<?php

namespace ZekyWolf\LGSQ\Protocols;

use ZekyWolf\LGSQ\Helpers\EServerParams as SParams;

class Query11
{
    public static function get(&$server, &$lgsl_need, &$lgsl_fp)
    {
        //---------------------------------------------------------+
        //  REFERENCE: http://wiki.unrealadmin.org/UT3_query_protocol
        //  UT3 RESPONSE IS REALLY MESSY SO THIS CLEANS IT UP

        $status = Query06::get($server, $lgsl_need, $lgsl_fp);

        if (!$status) {
            return false;
        }

        //---------------------------------------------------------+

        $server[SParams::SERVER]['map'] = $server[SParams::CONVARS]['p1073741825'];
        unset($server[SParams::CONVARS]['p1073741825']);

        //---------------------------------------------------------+

        $lgsl_ut3_key = [
            "s0"          => "bots_skill",
            "s6"          => "pure",
            "s7"          => "password",
            "s8"          => "bots_vs",
            "s10"         => "forcerespawn",
            "p268435703"  => "bots",
            "p268435704"  => "goalscore",
            "p268435705"  => "timelimit",
            "p268435717"  => "mutators_default",
            "p1073741826" => "gamemode",
            "p1073741827" => "description",
            "p1073741828" => "mutators_custom"
        ];

        foreach ($lgsl_ut3_key as $old => $new) {
            if (!isset($server[SParams::CONVARS][$old])) {
                continue;
            }
            $server[SParams::CONVARS][$new] = $server[SParams::CONVARS][$old];
            unset($server[SParams::CONVARS][$old]);
        }

        //---------------------------------------------------------+

        $part = explode(".", $server[SParams::CONVARS]['gamemode']);

        if ($part[0] && (stristr($part[0], "UT") === false)) {
            $server[SParams::SERVER]['game'] = $part[0];
        }

        //---------------------------------------------------------+

        $tmp = $server[SParams::CONVARS]['mutators_default'];
        $server[SParams::CONVARS]['mutators_default'] = "";

        if ($tmp & 1) {
            $server[SParams::CONVARS]['mutators_default'] .= " BigHead";
        }
        if ($tmp & 2) {
            $server[SParams::CONVARS]['mutators_default'] .= " FriendlyFire";
        }
        if ($tmp & 4) {
            $server[SParams::CONVARS]['mutators_default'] .= " Handicap";
        }
        if ($tmp & 8) {
            $server[SParams::CONVARS]['mutators_default'] .= " Instagib";
        }
        if ($tmp & 16) {
            $server[SParams::CONVARS]['mutators_default'] .= " LowGrav";
        }
        if ($tmp & 64) {
            $server[SParams::CONVARS]['mutators_default'] .= " NoPowerups";
        }
        if ($tmp & 128) {
            $server['e']['mutators_default'] .= " NoTranslocator";
        }
        if ($tmp & 256) {
            $server[SParams::CONVARS]['mutators_default'] .= " Slomo";
        }
        if ($tmp & 1024) {
            $server[SParams::CONVARS]['mutators_default'] .= " SpeedFreak";
        }
        if ($tmp & 2048) {
            $server[SParams::CONVARS]['mutators_default'] .= " SuperBerserk";
        }
        if ($tmp & 8192) {
            $server[SParams::CONVARS]['mutators_default'] .= " WeaponReplacement";
        }
        if ($tmp & 16384) {
            $server[SParams::CONVARS]['mutators_default'] .= " WeaponsRespawn";
        }

        $server[SParams::CONVARS]['mutators_default'] = str_replace(" ", " / ", trim($server[SParams::CONVARS]['mutators_default']));
        $server[SParams::CONVARS]['mutators_custom']  = str_replace("\x1c", " / ", $server[SParams::CONVARS]['mutators_custom']);

        //---------------------------------------------------------+

        return true;
    }
}
