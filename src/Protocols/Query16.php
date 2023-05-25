<?php

namespace ZekyWolf\LGSQ\Protocols;

use ZekyWolf\LGSQ\Helpers\{
    Parse\Unpack,
    Parse\ParseString,
    Parse\Time,
    EServerParams as SParams
};

class Query16
{
    public static function getlgsl_query_16(&$server, &$lgsl_need, &$lgsl_fp)
    {
        //---------------------------------------------------------+
        //  REFERENCE:
        //  http://www.planetpointy.co.uk/software/rfactorsspy.shtml
        //  http://users.pandora.be/viperius/mUtil/
        //  USES FIXED DATA POSITIONS WITH RANDOM CHARACTERS FILLING THE GAPS

        fwrite($lgsl_fp, "rF_S");

        $buffer = fread($lgsl_fp, 4096);

        if (!$buffer) {
            return false;
        }

        //---------------------------------------------------------+

        $buffer = substr($buffer, 8);
        $server[SParams::CONVARS]['region']           = Unpack::get($buffer[1] .$buffer[2], "S");
        $server[SParams::CONVARS]['version']          = Unpack::get($buffer[9] .$buffer[10], "S");
        $server[SParams::CONVARS]['hostport']         = Unpack::get($buffer[13].$buffer[14], "S");
        $buffer = substr($buffer, 17);
        $server[SParams::SERVER]['game']             = ParseString::get($buffer);
        $buffer = substr($buffer, 20);
        $server[SParams::SERVER]['name']             = ParseString::get($buffer);
        $buffer = substr($buffer, 28);
        $server[SParams::SERVER]['map']              = ParseString::get($buffer);
        $buffer = substr($buffer, 32);
        $server[SParams::CONVARS]['motd']             = ParseString::get($buffer);
        $buffer = substr($buffer, 96);
        $server[SParams::CONVARS]['packed_aids']      = Unpack::get($buffer[0].$buffer[1], "S");

        $server[SParams::CONVARS]['packed_flags']     = Unpack::get($buffer[4], "C");
        $server[SParams::CONVARS]['rate']             = Unpack::get($buffer[5], "C");
        $server[SParams::SERVER]['players']          = Unpack::get($buffer[6], "C");
        $server[SParams::SERVER]['playersmax']       = Unpack::get($buffer[7], "C");
        $server[SParams::CONVARS]['bots']             = Unpack::get($buffer[8], "C");
        $server[SParams::CONVARS]['packed_special']   = Unpack::get($buffer[9], "C");
        $server[SParams::CONVARS]['damage']           = Unpack::get($buffer[10], "C");
        $server[SParams::CONVARS]['packed_rules']     = Unpack::get($buffer[11].$buffer[12], "S");
        $server[SParams::CONVARS]['credits1']         = Unpack::get($buffer[13], "C");
        $server[SParams::CONVARS]['credits2']         = Unpack::get($buffer[14].$buffer[15], "S");
        $server[SParams::CONVARS]['time']   = Time::get(Unpack::get($buffer[16].$buffer[17], "S"));
        $server[SParams::CONVARS]['laps']             = Unpack::get($buffer[18].$buffer[19], "s") / 16;
        $buffer = substr($buffer, 23);
        $server[SParams::CONVARS]['vehicles']         = ParseString::get($buffer);

        // DOES NOT RETURN PLAYER INFORMATION

        //---------------------------------------------------------+

        $server[SParams::SERVER]['password']    = ($server[SParams::CONVARS]['packed_special'] & 2) ? 1 : 0;
        $server[SParams::CONVARS]['racecast']    = ($server[SParams::CONVARS]['packed_special'] & 4) ? 1 : 0;
        $server[SParams::CONVARS]['fixedsetups'] = ($server[SParams::CONVARS]['packed_special'] & 16) ? 1 : 0;

        $server[SParams::CONVARS]['aids']  = "";
        if ($server[SParams::CONVARS]['packed_aids'] & 1) {
            $server[SParams::CONVARS]['aids'] .= " TractionControl";
        }
        if ($server[SParams::CONVARS]['packed_aids'] & 2) {
            $server[SParams::CONVARS]['aids'] .= " AntiLockBraking";
        }
        if ($server[SParams::CONVARS]['packed_aids'] & 4) {
            $server[SParams::CONVARS]['aids'] .= " StabilityControl";
        }
        if ($server[SParams::CONVARS]['packed_aids'] & 8) {
            $server[SParams::CONVARS]['aids'] .= " AutoShifting";
        }
        if ($server[SParams::CONVARS]['packed_aids'] & 16) {
            $server[SParams::CONVARS]['aids'] .= " AutoClutch";
        }
        if ($server[SParams::CONVARS]['packed_aids'] & 32) {
            $server[SParams::CONVARS]['aids'] .= " Invulnerability";
        }
        if ($server[SParams::CONVARS]['packed_aids'] & 64) {
            $server[SParams::CONVARS]['aids'] .= " OppositeLock";
        }
        if ($server[SParams::CONVARS]['packed_aids'] & 128) {
            $server[SParams::CONVARS]['aids'] .= " SteeringHelp";
        }
        if ($server[SParams::CONVARS]['packed_aids'] & 256) {
            $server[SParams::CONVARS]['aids'] .= " BrakingHelp";
        }
        if ($server[SParams::CONVARS]['packed_aids'] & 512) {
            $server[SParams::CONVARS]['aids'] .= " SpinRecovery";
        }
        if ($server[SParams::CONVARS]['packed_aids'] & 1024) {
            $server[SParams::CONVARS]['aids'] .= " AutoPitstop";
        }

        $server[SParams::CONVARS]['aids']     = str_replace(" ", " / ", trim($server[SParams::CONVARS]['aids']));
        $server[SParams::CONVARS]['vehicles'] = str_replace("|", " / ", trim($server[SParams::CONVARS]['vehicles']));

        unset($server[SParams::CONVARS]['packed_aids']);
        unset($server[SParams::CONVARS]['packed_flags']);
        unset($server[SParams::CONVARS]['packed_special']);
        unset($server[SParams::CONVARS]['packed_rules']);

        //---------------------------------------------------------+

        return true;
    }
}
