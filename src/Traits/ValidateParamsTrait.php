<?php

namespace ZekyWolf\LGSQ\Traits;

use ZekyWolf\LGSQ\{
    Helpers\ProtocolList,
    Params\EConnectionParams as CParams,
};

trait ValidateParamsTrait
{
    public function validate(array $serverData)
    {
        if (!array_key_exists(CParams::TYPE, $serverData) || empty($serverData[CParams::TYPE])) {
            throw new \Exception("Missing server type key '" . CParams::TYPE . "'!");
        }

        if (!array_key_exists(CParams::IP, $serverData) || empty($serverData[CParams::IP])) {
            throw new \Exception("Missing server type key '" . CParams::IP . "'!");
        }

        /**
         * ? IS VALID IP/HOSTNAME?
         */
        if (preg_match("/[^0-9a-zA-Z\.\-\[\]\:]/i", $serverData[CParams::IP])) {
            throw new \Exception("Invalid ip/hostname, '" . CParams::IP . "'!");
        }

        /**
         * ? IS VALID QUERY PORT?
         */
        if (!intval($serverData[CParams::QPORT])) {
            throw new \Exception("Invalid qport, '" . CParams::QPORT . "'!");
        }

        $protocol = ProtocolList::get();

        /**
         * ? EXIST PROTOCOL FOR GAME TYPE?
         */
        if (!isset($protocol[$serverData[CParams::TYPE]])) {
            throw new \Exception("Invalid protocol, '" . $protocol[$serverData[CParams::TYPE]] . "'!");
        }

        /**
         * ? EXIST CLASS FOR GAME TYPE?
         */
        $classCheck = "\\ZekyWolf\\LGSQ\\Protocols\\Query{$protocol[$serverData[CParams::TYPE]]}";
        if (!class_exists($classCheck)) {
            throw new \Exception("Invalid class, '" . $classCheck . "'!");
        }
    }

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
