<?php

namespace ZekyWolf\LGSQ\Helpers;

use ZekyWolf\LGSQ\Helpers\Protocols;

class ProtocolList
{
    public static function get(): array
    {
        $list = [
            Protocols::AMERICASARMY             =>      '09',
            Protocols::AMERICASARMY_            =>      '03',
            Protocols::AMERICASARMY3            =>      '26',
            Protocols::ARCASIMRACING            =>      '16',
            Protocols::ARMA                     =>      '09',
            Protocols::ARMA2                    =>      '09',
            Protocols::ARMA3                    =>      '05',
            Protocols::ALIENSVSPREDATOR2        =>      '03',
            Protocols::ALIENSVSPREDATOR2_2010   =>      '31',
            Protocols::BATTLEFIELDBADCOMPANY2   =>      '30',
            Protocols::BATTLEFIELDVIETNAM       =>      '09',
            Protocols::BATTLEFIELD1942          =>      '03',
            Protocols::BATTLEFIELD2             =>      '06',
            Protocols::BATTLEFIELD3             =>      '30',
            Protocols::BATTLEFIELD4             =>      '06',
            Protocols::BATTLEFIELD2142          =>      '06',
            Protocols::CALLOFDUTY               =>      '02',
            Protocols::CALLOFDUTYBO3            =>      '05',
            Protocols::CALLOFDUTYIW             =>      '02',
            Protocols::CALLOFDUTYOU             =>      '02',
            Protocols::CALLOFDUTYWAW            =>      '02',
            Protocols::CALLOFDUTY2              =>      '02',
            Protocols::CALLOFDUTY4              =>      '02',
            Protocols::COMMANDANDCONQUER        =>      '03',
            Protocols::CONANEXILES              =>      '05',
            Protocols::CRYSIS                   =>      '06',
            Protocols::CRYSISWARS               =>      '06',
            Protocols::CS2D                     =>      '29',
            Protocols::CUBE                     =>      '24',
            Protocols::DISCORD                  =>      '36',
            Protocols::DOOMSSKULLTAG            =>      '27',
            Protocols::DOOMZDEAMON              =>      '28',
            Protocols::DOOM3                    =>      '10',
            Protocols::DEERHUNTER2005           =>      '09',
            Protocols::ECO                      =>      '40',
            Protocols::FACTORIO                 =>      '42',
            Protocols::HIDDENANDDANGEROUSE2     =>      '03',
            Protocols::HALFLIFE                 =>      '05',
            Protocols::HALFLIFEWON              =>      '05',
            Protocols::HALO                     =>      '03',
            Protocols::IL22STURMOVIK            =>      '03',
            Protocols::FARCRY                   =>      '08',
            Protocols::FARMINGSIMULATOR         =>      '40',
            Protocols::FEAR                     =>      '09',
            Protocols::FIVEM                    =>      '35',
            Protocols::OPERATIONFLASHPOINT      =>      '03',
            Protocols::FREELANCER               =>      '14',
            Protocols::FRONTLINES               =>      '20',
            Protocols::F1CHALLENGE9902          =>      '03',
            Protocols::GENERICSPY1              =>      '03',
            Protocols::GENERICSPY2              =>      '09',
            Protocols::GENERICSPY3              =>      '06',
            Protocols::GHOSTRECON               =>      '19',
            Protocols::GRAW                     =>      '06',
            Protocols::GRAW2                    =>      '09',
            Protocols::GTR2                     =>      '15',
            Protocols::JEDIKNIGHT2              =>      '02',
            Protocols::JEDIKNIGHTJA             =>      '02',
            Protocols::JUSTCOUSE2MP             =>      '06',
            Protocols::KILLINGFLOOR             =>      '13',
            Protocols::KINGPIN                  =>      '03',
            Protocols::MAFIA2MP                 =>      '39',
            Protocols::MINECRAFT                =>      '06',
            Protocols::MOHAA                    =>      '03',
            Protocols::MOHAAB                   =>      '03',
            Protocols::MOHAAS                   =>      '03',
            Protocols::MOHPA                    =>      '03',
            Protocols::MOHAA_                   =>      '02',
            Protocols::MOHAAB_                  =>      '02',
            Protocols::MOHAAS_                  =>      '02',
            Protocols::MOHPA_                   =>      '02',
            Protocols::MTA                      =>      '08',
            Protocols::MUMBLE                   =>      '43',
            Protocols::NASCAR2004               =>      '09',
            Protocols::NEVERWINTER              =>      '09',
            Protocols::NEVERWINTER2             =>      '09',
            Protocols::NEXUIZ                   =>      '02',
            Protocols::OPENTTD                  =>      '22',
            Protocols::PAINKILLER               =>      '08',
            Protocols::PAINKILLER_              =>      '09',
            Protocols::PLAINSIGHT               =>      '32',
            Protocols::PREY                     =>      '10',
            Protocols::QUAKEWORLD               =>      '07',
            Protocols::QUAKEWARS                =>      '10',
            Protocols::QUAKE2                   =>      '02',
            Protocols::QUAKE3                   =>      '02',
            Protocols::QUAKE4                   =>      '10',
            Protocols::RAGEMP                   =>      '34',
            Protocols::RAVENSHIELD              =>      '04',
            Protocols::REDORCHESTRA             =>      '13',
            Protocols::RFACTOR                  =>      '16',
            Protocols::SAMP                     =>      '12',
            Protocols::SAVAGE                   =>      '17',
            Protocols::SAVAGE2                  =>      '18',
            Protocols::SERIOUSSAM               =>      '03',
            Protocols::SERIOUSSAM2              =>      '09',
            Protocols::SCUM                     =>      '37',
            Protocols::SF                       =>      '41',
            Protocols::SHATTEREDHORIZON         =>      '05',
            Protocols::SOLDIEROFFORTUNE2        =>      '02',
            Protocols::SOLDAT                   =>      '08',
            Protocols::SOURCE                   =>      '05',
            Protocols::CS16                     =>      '05',
            Protocols::CSGO                     =>      '05',
            Protocols::STALKER                  =>      '06',
            Protocols::STALKERCOP               =>      '09',
            Protocols::STALKERCS                =>      '09',
            Protocols::STARTREKEF               =>      '02',
            Protocols::STARWARSBF               =>      '09',
            Protocols::STARWARSBF2              =>      '09',
            Protocols::STARWARSRC               =>      '09',
            Protocols::SWAT4                    =>      '03',
            Protocols::TEST                     =>      '01',
            Protocols::TEENWORLDS               =>      '21',
            Protocols::TERRARIA                 =>      '38',
            Protocols::TRIBES                   =>      '23',
            Protocols::TRIBES2                  =>      '25',
            Protocols::TRIBESV                  =>      '09',
            Protocols::TEAMSPEAK                =>      '33',
            Protocols::TS                       =>      '33',
            Protocols::TS3                      =>      '33',
            Protocols::TEAMSPEAK3               =>      '33',
            Protocols::TEASPEAK                 =>      '33',
            Protocols::WARSOW                   =>      '02',
            Protocols::WARSOWOLD                =>      '02',
            Protocols::URBANTERROR              =>      '02',
            Protocols::UNREALTOURNAMENT         =>      '03',
            Protocols::UNREALTOURNAMENT2003     =>      '13',
            Protocols::UNREALTOURNAMENT2003_    =>      '03',
            Protocols::UNREALTOURNAMENT2004     =>      '13',
            Protocols::UNREALTOURNAMENT2004_    =>      '03',
            Protocols::UNREALTOURNAMENT3        =>      '11',
            Protocols::VCMP                     =>      '12',
            Protocols::VIETCONG                 =>      '03',
            Protocols::VIETCONG2                =>      '09',
            Protocols::WOLFENSTEINET            =>      '02',
            Protocols::WOLFENSTEINFRTCW         =>      '02',
            Protocols::WOLFENSTEIN2009          =>      '10',
            Protocols::WOW                      =>      '41',
        ];

        return $list;
    }
}
