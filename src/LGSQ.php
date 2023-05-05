<?php

namespace ZekyWolf\LGSQ;

use ZekyWolf\LGSQ\Helpers\{
    ERequestParams as RParams,
    EServerParams as SParams,
    GameTypeScheme,
    ProtocolList
};

class LGSQ
{
    private string $type;
    private string $ip;
    private int $c_port;
    private int $q_port;
    private int $s_port;
    private array $request;
    private array $server;
    public array $custom_data;

    /**
     * MAJOR
     * MINOR
     * PATCH
     */
    const VERSION = '1.0.1';

    /**
     * 
     * @param $type         Game type
     * @param $ip           Server IP/Hostname
     * @param $c_port       Connection port
     * @param $q_port       Query Port
     * @param $request      Requested data, 
     *                      valid: 
     *                          > Array string, only those 3 are valid, any others will be ignored
     *                          [ "s", "p", "c" ]
     *                          > Or usage via ERequestParams abastract class:
     *                          [ RParams::SERVER, SParams::PLAYERS, SParams::CONVARS]
     * @param $cdata        Custom data, default []
     * @param $s_port       Server port, default 0
     * 
     * @noreturn
     *
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
        string $ip,
        int $c_port,
        int $q_port,
        array $request = [],
        array $cdata = [],
        int $s_port = 0,
    ) {
        $this->type = $type;
        $this->ip = $ip;
        $this->c_port = $c_port;
        $this->q_port = $q_port;
        $this->s_port = $s_port;
        $this->request  = $request;
        $this->custom_data = $cdata;

        $this->server = [
            // b
            SParams::BASIC => [
                'type' => $this->type,
                'ip' => $this->ip,
                'c_port' => $this->c_port,
                'q_port' => $this->q_port,
                's_port' => $this->s_port,
                'status' => 1,
                '_error' => null,
            ],

            // s
            SParams::SERVER => [
                'game' => '',
                'name' => '',
                'map' => '',
                'players' => 0,
                'playersmax' => 0,
                'password' => '',
            ],

            // e
            SParams::CONVARS => [],

            // p
            SParams::PLAYERS => [],

            // t
            SParams::TEAMS => [],
        ];

        $this->CheckAndConnect();
    }

    /**
     * This checking if there are valid data.
     */
    public function CheckAndConnect(): bool|null|array
    {
        /**
         * ? IS VALID IP/HOSTNAME?
         */
        if (preg_match("/[^0-9a-zA-Z\.\-\[\]\:]/i", $this->ip)) {
            $this->server[SParams::BASIC]['status'] = 0;
            $this->server[SParams::BASIC]['_error'] = "LGSQ: Invalid ip and/or hostname.";

            return false;
        }

        /**
         * ? IS VALID QUERY PORT?
         */
        if (!intval($this->q_port)) {
            $this->server[SParams::BASIC]['status'] = 0;
            $this->server[SParams::BASIC]['_error'] = "LGSQ: INVALID QUERY PORT";

            return false;
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
                'For IP/HOSTNAME: '.$this->ip.', Port: '.$this->c_port
            ];

            return false;
        }

        $classCheck = "\\ZekyWolf\\LGSQ\\Protocols\\Query{$protocol[$this->type]}";
        if (!class_exists($classCheck)) {
            $this->server[SParams::BASIC]['status'] = 0;
            $this->server[SParams::BASIC]['_error'] = "Invalid query class name, {$classCheck}";

            return false;
        }

        return $this->Retrive();
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

            $this->server[SParams::SERVER]['cache_time'] = time();
        }

        $this->server;
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
                "{$scheme}://{$server[SParams::BASIC]['ip']}",
                $server[SParams::BASIC]['q_port'],
                $errno,
                $errstr,
                1
            );

            if (! $lgsl_fp) {
                $server[SParams::CONVARS]['_error'] = $errstr;

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
        return $this->custom_data;
    }
}
