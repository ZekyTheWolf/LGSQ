<?php

namespace ZekyWolf\LGSQ\Traits;

use ZekyWolf\LGSQ\{
    Helpers\ProtocolList,
    Params\EConnectionParams as CParams,
    Params\ERequestParams as RParams,
    Params\EServerParams as SParams,
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

    public function validateResponse()
    {
        if (empty($this->server[SParams::SERVER]['game'])) {
            $this->server[SParams::SERVER]['game'] = $this->server[SParams::BASIC][CParams::TYPE];
        }
        if (empty($this->server[SParams::SERVER]['map'])) {
            $this->server[SParams::SERVER]['map'] = '-';
        }

        if (($pos = strrpos($this->server[SParams::SERVER]['map'], '/')) !== false) {
            $this->server[SParams::SERVER]['map'] = substr($this->server[SParams::SERVER]['map'], $pos + 1);
        }
        if (($pos = strrpos($this->server[SParams::SERVER]['map'], '\\')) !== false) {
            $this->server[SParams::SERVER]['map'] = substr($this->server[SParams::SERVER]['map'], $pos + 1);
        }

        $this->server[SParams::SERVER]['players'] = intval($this->server[SParams::SERVER]['players']);
        $this->server[SParams::SERVER]['playersmax'] = intval($this->server[SParams::SERVER]['playersmax']);

        if (isset($this->server[SParams::SERVER]['password'][0])) {
            $this->server[SParams::SERVER]['password'] = (strtolower($this->server[SParams::SERVER]['password'][0]) == 't') ? 1 : 0;
        } else {
            $this->server[SParams::SERVER]['password'] = intval($this->server[SParams::SERVER]['password']);
        }

        if (
            in_array(RParams::SERVER, $this->request)
            && empty($this->server[SParams::PLAYERS])
            && $this->server[SParams::SERVER]['players'] != 0
        ) {
            unset($this->server[SParams::PLAYERS]);
        }

        if (in_array(RParams::PLAYERS, $this->request) && empty($this->server[SParams::TEAMS])) {
            unset($this->server[SParams::TEAMS]);
        }

        if (in_array(RParams::CONVARS, $this->request) && empty($this->server[SParams::CONVARS])) {
            unset($this->server[SParams::CONVARS]);
        }

        if (in_array(RParams::SERVER, $this->request) && empty($this->server[SParams::SERVER])) {
            unset($this->server[SParams::SERVER]);
        }
    }
}
