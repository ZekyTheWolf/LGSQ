<?php

namespace ZekyWolf\LGSQ\Protocols;

use ZekyWolf\LGSQ\Helpers\EServerParams as SParams;

class Query01
{
    public static function get(&$server, &$lgsl_need, &$lgsl_fp): bool
    {
        //---------------------------------------------------------+
        //  PROTOCOL FOR DEVELOPING WITHOUT USING LIVE SERVERS TO HELP ENSURE RETURNED
        //  DATA IS SANITIZED AND THAT LONG SERVER AND PLAYER NAMES ARE HANDLED PROPERLY
        $server[SParams::SERVER] = [
            'game' => 'test_game',
            'name' => "test_ServerNameThatsOften'Really'LongAndCanHaveSymbols<hr /        >ThatWill\"Screw\"UpHtmlUnlessEntitied",
            'map' => 'test_map',
            'players' => rand(0, 16),
            'playersmax' => rand(16, 32),
            'password' => rand(0, 1),
        ];

        //---------------------------------------------------------+
        $server[SParams::CONVARS] = [
            'testextra1' => 'normal',
            'testextra2' => 123,
            'testextra3' => time(),
            'testextra4' => '',
            'testextra5' => '<b>Setting<hr />WithHtml</b>',
            'testextra6' => 'ReallyLongSettingLikeSomeMapCyclesThatHaveNoSpacesAndCauseThePageToGoReallyWideIfNotBrokenUp',
        ];

        //---------------------------------------------------------+
        $server[SParams::PLAYERS]['0']['name'] = 'Normal';
        $server[SParams::PLAYERS]['0']['score'] = '12';
        $server[SParams::PLAYERS]['0']['ping'] = '34';

        // UTF PLAYER NAME
        $server[SParams::PLAYERS]['1']['name'] = "\xc3\xa9\x63\x68\x6f\x20\xd0\xb8-d0\xb3\xd1\x80\xd0\xbe\xd0\xba";
        $server[SParams::PLAYERS]['1']['score'] = '56';
        $server[SParams::PLAYERS]['1']['ping'] = '78';

        $server[SParams::PLAYERS]['2']['name'] = "One&<Two>&Three&\"Four\"&'Five'";
        $server[SParams::PLAYERS]['2']['score'] = '90';
        $server[SParams::PLAYERS]['2']['ping'] = '12';

        $server[SParams::PLAYERS]['3']['name'] = 'ReallyLongPlayerNameBecauseTheyAreUberCoolAndAreInFiveClans';
        $server[SParams::PLAYERS]['3']['score'] = '90';
        $server[SParams::PLAYERS]['3']['ping'] = '12';

        //---------------------------------------------------------+
        if (rand(0, 10) == 5) {
            $server[SParams::PLAYERS] = [];
        } // RANDOM NO PLAYERS
        if (rand(0, 10) == 5) {
            return false;
        }           // RANDOM GOING OFFLINE

        //---------------------------------------------------------+
        return true;
    }
}
