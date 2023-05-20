<?php

namespace ZekyWolf\LGSQ;

use ZekyWolf\LGSQ\Helpers\{
    ERequestParams as RParams,
    EServerParams as SParams,
    EConnectionParams as CParams,
    Games,
    GameTypeScheme,
    ProtocolList
};

class LGSQ
{
    /**
     * Recommend using Games scheme.
     */
    private string $type;
    private array $request;
    private array $server;

    /**
     * MAJOR
     * MINOR
     * PATCH
     */
    public const LGSQ_VERSION = '1.1.4';

    /**
     * 
     * @param $type
     * @param $serverData
     * @param $request
     * @param $cdata
     * @param $s_port
     * 
     * @noreturn
     * Valid Data for $serverData param
     * Data for query server in array [ 'ip' => '1.0.0.0', 'port' => 1, 'qport' => 0 ]
     * 
     * Valid Data for $request param
     * Array string, only those 3 are valid, any others will be ignored
     *      [ "s", "p", "c" ]
     * or usage via ERequestParams abastract class:
     *      [ ERequestParams::SERVER, ERequestParams::PLAYERS, ERequestParams::CONVARS]
     * Explanation:
     * Since this is rebuild of LGSL to be more compatibile with Laravel and more PHP Frameworks
     * i decided to make few changes, one visible is in $request.
     *
     * Request is now a array of values, you can use directly 's', 'p', 'c' for specified request
     * but i would recommend using abstract class of ERequestParams where are stored params for request
     * this way you can avoid any potential errors.
     */
    public function __construct(
        string $type,
        array $serverData,
        array $request = [],
        array $cdata = [],
    ) {
        $this->type = $type;
        $this->request  = $request;

        $this->server = [
            SParams::BASIC => [
                CParams::TYPE => $this->type,
                CParams::IP => $this->clearHostName($this->type, $serverData[CParams::IP]),
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

        $this->CheckAndConnect();
    }

    /**
     * This checking if there are valid data.
     */
    public function CheckAndConnect()
    {
        /**
         * ? IS VALID IP/HOSTNAME?
         */
        if (preg_match("/[^0-9a-zA-Z\.\-\[\]\:]/i", $this->server[SParams::BASIC][CParams::IP])) {
            $this->server[SParams::BASIC]['status'] = 0;
            $this->server[SParams::BASIC]['_error'] = "LGSQ: Invalid ip and/or hostname.";
        }

        /**
         * ? IS VALID QUERY PORT?
         */

        if (!intval($this->server[SParams::BASIC][CParams::QPORT])) {
            $this->server[SParams::BASIC]['status'] = 0;
            $this->server[SParams::BASIC]['_error'] = "LGSQ: INVALID QUERY PORT";
        }

        /**
         * ? EXIST PROTOCOL FOR GAME TYPE?
         */
        $protocol = ProtocolList::get();

        if (!isset($protocol[$this->type])) {
            $this->server[SParams::BASIC]['status'] = 0;
            $this->server[SParams::BASIC]['_error'] = [
                'LGSQ:',
                $this->type ? "INVALID TYPE '{$this->type}'" : 'MISSING TYPE',
                'For IP/HOSTNAME: '.$this->server[SParams::BASIC][CParams::IP].', Port: '.$this->server[SParams::BASIC][CParams::QPORT]
            ];
        }

        $classCheck = "\\ZekyWolf\\LGSQ\\Protocols\\Query{$protocol[$this->type]}";
        if (!class_exists($classCheck)) {
            $this->server[SParams::BASIC]['status'] = 0;
            $this->server[SParams::BASIC]['_error'] = "Invalid query class name, {$classCheck}";
        }

        if(!$this->server[SParams::BASIC]['_error']){
            $this->Retrive();
        }
    }

    /**
     * This is connecting to server and retriving data
     */
    private function Retrive()
    {
        $protocol = ProtocolList::get();

        $class = "\\ZekyWolf\\LGSQ\\Protocols\\Query{$protocol[$this->type]}";

        if ($class == '\\ZekyWolf\\LGSQ\\Protocols\\Query01') { // TEST RETURNS DIRECT
            $lgsl_need = '';
            $lgsl_fp = '';
            $response = call_user_func_array([$class, 'get'], [&$this->server, &$lgsl_need, &$lgsl_fp]);

            return $this->server;
        }

        $response = $this->queryDirect($this->server, $this->request, $class, GameTypeScheme::get($this->type));

        if (! $response) { // SERVER OFFLINE
            $this->server[SParams::BASIC]['status'] = 0;
        } else {
            if (empty($this->server[SParams::SERVER]['game'])) {
                $this->server[SParams::SERVER]['game'] = $this->type;
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
        $timeout = 5.0;

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
            curl_setopt($lgsl_fp, CURLOPT_CONNECTTIMEOUT, $timeout);
            curl_setopt($lgsl_fp, CURLOPT_TIMEOUT, 3);
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

            stream_set_timeout($lgsl_fp, $timeout, $timeout ? 0 : 500000);
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

    public function getCustomData(): array
    {
        return $this->server[SParams::CUSTOM_DATA];
    }

    private function clearHostName(string $type, string $ip): string
    {
        if(
            $type == Games::DISCORD && 
            (str_contains($ip, 'discord.gg') || str_contains($ip, 'https://discord.gg'))
        ){
            return str_replace([
                'https://discord.gg/',
                'discord.gg/',
            ], "", $ip);
        }

        return str_replace(' ', '', $ip);
    }
}
