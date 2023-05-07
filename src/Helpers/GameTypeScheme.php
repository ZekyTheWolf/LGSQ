<?php

namespace ZekyWolf\LGSQ\Helpers;

class GameTypeScheme
{
    public static function get(string $type): string
    {
        $schemes = [
            Games::BATTLEFIELDBADCOMPANY2 => 'tcp',
            Games::BATTLEFIELD3 => 'tcp',
            Games::DISCORD => 'http',
            Games::ECO => 'http',
            Games::FARMINGSIMULATOR => 'http',
            Games::FIVEM => 'http',
            Games::RAGEMP => 'http',
            Games::SCUM => 'http',
            Games::TERRARIA => 'http',
            Games::TS => 'tcp',
            Games::TS3 => 'tcp',
            Games::TEASPEAK => 'tcp',
            Games::WOW => 'tcp',
        ];

        return isset($schemes[$type]) ? $schemes[$type] : 'udp';
    }
}
