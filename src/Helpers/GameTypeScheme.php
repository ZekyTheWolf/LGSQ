<?php

namespace ZekyWolf\LGSQ\Helpers;

class GameTypeScheme
{
    public static function get(string $type): string
    {
        $schemes = [
            'bfbc2' => 'tcp',
            'bf3' => 'tcp',
            'discord' => 'http',
            'eco' => 'http',
            'farmsim' => 'http',
            'fivem' => 'http',
            'ragemp' => 'http',
            'scum' => 'http',
            'terraria' => 'http',
            'ts' => 'tcp',
            'ts3' => 'tcp',
            'teaspeak' => 'tcp',
            'wow' => 'tcp',
        ];

        return isset($schemes[$type]) ? $schemes[$type] : 'udp';
    }
}
