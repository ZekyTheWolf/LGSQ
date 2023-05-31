<?php

namespace ZekyWolf\LGSQ;

use ZekyWolf\LGSQ\{
    Params\ERequestParams as RParams,
    Params\EServerParams as SParams,
    Params\EConnectionParams as CParams,
    Helpers\ProtocolsTypeScheme,
    Helpers\ProtocolList,
    Traits\ValidateParamsTrait,
    Traits\OptionsTrait,
};

class LGSQ
{
    use ValidateParamsTrait;
    use OptionsTrait;

    /**
     * Recommend using Games scheme.
     */
    private array $request;
    private array $server;

    /**
     * MAJOR
     * MINOR
     * PATCH
     */
    public const LGSQ_VERSION = '1.4.0-DEV';

    /**
     * Validate server data, connect and set data.
     *
     * @param array $serverData
     * @param array $request
     * @param array $cdata
     *
     * @return void
     */
    public function __construct(
        array $serverData,
        array $request = [ RParams::SERVER, RParams::CONVARS, RParams::PLAYERS ],
        array $cdata = [],
    ) {

        $this->validate($serverData);

        $this->server = [
            SParams::BASIC => [
                CParams::TYPE => $serverData[CParams::TYPE],
                CParams::IP => $this->clearHostName($serverData[CParams::IP]),
                CParams::PORT => isset($serverData[CParams::PORT]) ? $serverData[CParams::PORT] : 1,
                CParams::QPORT => isset($serverData[CParams::QPORT]) ? $serverData[CParams::QPORT] : 1,
                CParams::SPORT => isset($serverData[CParams::SPORT]) ? $serverData[CParams::SPORT] : 1,
                CParams::STATUS => 1,
                CParams::ERROR => null,
            ],
            SParams::SERVER => [
                'game' => '',
                'name' => '',
                'map' => '',
                'players' => 0,
                'playersmax' => 0,
                'password' => '',
            ],
            SParams::CONVARS => [],
            SParams::PLAYERS => [],
            SParams::TEAMS => [],
            SParams::CUSTOM_DATA => $cdata,
        ];

        $this->request = $request;

        $this->fetch();
    }

    /**
     * This is connecting to server and retriving data
     */
    private function fetch()
    {
        $protocol = ProtocolList::get();

        $class = "\\ZekyWolf\\LGSQ\\Protocols\\Query{$protocol[$this->server[SParams::BASIC][CParams::TYPE]]}";

        if ($class == '\\ZekyWolf\\LGSQ\\Protocols\\Query01') { // TEST RETURNS DIRECT
            $lgsl_need = '';
            $lgsl_fp = '';
            $response = call_user_func_array([$class, 'get'], [&$this->server, &$lgsl_need, &$lgsl_fp]);

            return $this->server;
        }

        $response = $this->queryDirect(
            $this->server,
            $this->request,
            $class,
            ProtocolsTypeScheme::get($this->server[SParams::BASIC][CParams::TYPE])
        );

        if (! $response) { // SERVER OFFLINE
            $this->server[SParams::BASIC]['status'] = 0;
        } else {
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

    /**
     * This actaully retrive all server data, format them and return it
     *
     * Private function, because cant be used outside this file.
     */
    private function queryDirect(&$server, array $request, $function, $scheme)
    {
        if ($scheme == 'http') {
            if (
                ! function_exists('curl_init') ||
                ! function_exists('curl_setopt') ||
                ! function_exists('curl_exec')
            ) {
                return false;
            }

            $lgsl_fp = curl_init('');
            curl_setopt($lgsl_fp, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($lgsl_fp, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($lgsl_fp, CURLOPT_CONNECTTIMEOUT, self::$options['curl_connect_timeout']);
            curl_setopt($lgsl_fp, CURLOPT_TIMEOUT, self::$options['curl_timeout']);
            curl_setopt($lgsl_fp, CURLOPT_HTTPHEADER, ['Accept: application/json']);
        } else {
            $lgsl_fp = @fsockopen(
                "{$scheme}://{$server[SParams::BASIC][CParams::IP]}",
                $server[SParams::BASIC][CParams::QPORT],
                $errno,
                $errstr,
                1
            );

            if (! $lgsl_fp) {
                $server[SParams::CONVARS][CParams::ERROR] = $errstr;

                return false;
            }

            stream_set_timeout($lgsl_fp, self::$options['curl_connect_timeout'], self::$options['curl_connect_timeout'] ? 0 : 500000);
            stream_set_blocking($lgsl_fp, true);
        }

        $lgsl_need = [];
        $lgsl_need[RParams::SERVER] = in_array(RParams::SERVER, $request) !== false ? true : false;
        $lgsl_need[RParams::CONVARS] = in_array(RParams::CONVARS, $request) !== false ? true : false;
        $lgsl_need[RParams::PLAYERS] = in_array(RParams::PLAYERS, $request) !== false ? true : false;

        if ($lgsl_need[RParams::CONVARS] && ! $lgsl_need[RParams::SERVER]) {
            $lgsl_need[RParams::SERVER] = true;
        }

        do {
            $lgsl_need_check = $lgsl_need;

            $response = call_user_func_array(
                [$function, 'get'],
                [&$server, &$lgsl_need, &$lgsl_fp]
            );

            if (! $response || $lgsl_need_check == $lgsl_need) {
                break;
            }

            if ($lgsl_need[RParams::PLAYERS] && $server[SParams::SERVER]['players'] == '0') {
                $lgsl_need[RParams::PLAYERS] = false;
            }
        } while (
            $lgsl_need[RParams::SERVER] == true ||
            $lgsl_need[RParams::CONVARS] == true ||
            $lgsl_need[RParams::PLAYERS] == true
        );

        if ($scheme == 'http') {
            curl_close($lgsl_fp);
        } else {
            @fclose($lgsl_fp);
        }

        return $response;
    }

    /**
     * Retrive all data
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->server;
    }

    /**
     * Retrive server data
     *
     * @return array
     */
    public function getBasicData(): array
    {
        return $this->server[SParams::BASIC];
    }

    /**
     * Retrive server data
     *
     * @return array
     */
    public function getServerData(): array
    {
        return $this->server[SParams::SERVER];
    }

    /**
     * Retrive players data
     *
     * @return array
     */
    public function getPlayers(): array
    {
        return $this->server[SParams::PLAYERS];
    }

    /**
     * Retrive teams data
     *
     * @return array
     */
    public function getTeams(): array
    {
        return $this->server[SParams::TEAMS];
    }

    /**
     * Retrive convars data
     *
     * @return array
     */
    public function getConvars(): array
    {
        return $this->server[SParams::CONVARS];
    }

    /**
     * Retrive custom data
     *
     * @return array
     */
    public function getCustomData(): array
    {
        return $this->server[SParams::CUSTOM_DATA];
    }
}
