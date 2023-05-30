<?php

namespace ZekyWolf\LGSQ\Helpers;

class ProtocolsTypeScheme
{
    public static function get(string $type): string
    {
        $schemes = [
            Protocols::BATTLEFIELDBADCOMPANY2 => 'tcp',
            Protocols::BATTLEFIELD3 => 'tcp',
            Protocols::DISCORD => 'http',
            Protocols::ECO => 'http',
            Protocols::FARMINGSIMULATOR => 'http',
            Protocols::FIVEM => 'http',
            Protocols::RAGEMP => 'http',
            Protocols::SCUM => 'http',
            Protocols::TERRARIA => 'http',
            Protocols::TS => 'tcp',
            Protocols::TS3 => 'tcp',
            Protocols::TEASPEAK => 'tcp',
            Protocols::WOW => 'tcp',
        ];

        return isset($schemes[$type]) ? $schemes[$type] : 'udp';
    }
}
