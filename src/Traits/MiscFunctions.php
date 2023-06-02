<?php

namespace ZekyWolf\LGSQ\Traits;

trait MiscFunctions
{
    public function clearHostName(string $ip): string
    {
        if((str_contains($ip, 'discord.gg') || str_contains($ip, 'https://discord.gg'))) {
            return str_replace([
                'https://discord.gg/',
                'discord.gg/',
            ], "", $ip);
        }

        return str_replace(' ', '', $ip);
    }
}
