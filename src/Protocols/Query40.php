<?php

namespace ZekyWolf\LGSQ\Protocols;

use ZekyWolf\LGSQ\Helpers\{
    EServerParams as SParams,
    EConnectionParams as CParams
};

class Query40
{
    public static function get(&$server, &$lgsl_need, &$lgsl_fp) // HTTP CRAWLER
    {
        $urls = array(
          'farmsim' => "http://{$server[SParams::BASIC][CParams::IP]}:{$server[SParams::BASIC][CParams::QPORT]}/index.html",
          'eco' => "http://{$server[SParams::BASIC][CParams::IP]}:{$server[SParams::BASIC][CParams::QPORT]}/info"
        );
        curl_setopt($lgsl_fp, CURLOPT_URL, $urls[$server[SParams::BASIC][CParams::TYPE]]);
        $buffer = curl_exec($lgsl_fp);
        if (!$buffer) {
            return false;
        }

        switch ($server[SParams::BASIC][CParams::TYPE]) {
            // Farming Simulator // CAN QUERY ONLY SERVER NAME AND ONLINE STATUS, MEH
            case 'farmsim': {
                preg_match('/<h2>Login to [\w\d\s\/\\&@"\'-]+<\/h2>/', $buffer, $name);

                $server[SParams::SERVER]['name']        = substr($name[0], 12, strlen($name[0])-17);
                $server[SParams::SERVER]['map']         = "Farm";

                return strpos($buffer, 'status-indicator online') !== false;
            }
                // ECO
            case 'eco': {
                $buffer = json_decode($buffer, true);

                $server[SParams::SERVER]['name']        = strip_tags($buffer['Description']);
                $server[SParams::SERVER]['map']         = "World";
                $server[SParams::SERVER]['players']     = $buffer['OnlinePlayers'];
                $server[SParams::SERVER]['playersmax']  = $buffer['TotalPlayers'];
                $server[SParams::SERVER]['password']    = (int) $buffer['HasPassword'];

                if ($server[SParams::SERVER]['players']) {
                    foreach ($buffer['OnlinePlayersNames'] as $key => $value) {
                        $server[SParams::PLAYERS][$key]['name'] = $value;
                    }
                }

                function t($t, $s = 0)
                {
                    return (int)($t / 86400) + $s . " days " . ($t / 86400 % 3600) . " hrs " . ($t / 3600 % 60) . " mins";
                }
                $server[SParams::CONVARS]['Laws']               = $buffer['Laws'];
                $server[SParams::CONVARS]['Plants']             = $buffer['Plants'];
                $server[SParams::CONVARS]['Animals']            = $buffer['Animals'];
                $server[SParams::CONVARS]['Version']            = $buffer['Version'];
                $server[SParams::CONVARS]['Discord']            = $buffer['DiscordAddress'];
                $server[SParams::CONVARS]['JoinUrl']            = $buffer['JoinUrl'];
                $server[SParams::CONVARS]['WorldSize']          = $buffer['WorldSize'];
                $server[SParams::CONVARS]['EconomyDesc']        = $buffer['EconomyDesc'];
                $server[SParams::CONVARS]['description']        = $buffer['DetailedDescription'];
                $server[SParams::CONVARS]['PeakActivePlayers']  = $buffer['PeakActivePlayers'];
                $server[SParams::CONVARS]['TimeSinceStart']     = t($buffer['TimeSinceStart'], 1);
                $server[SParams::CONVARS]['HasMeteor']          = $buffer['HasMeteor'];
                if ($buffer['HasMeteor']) {
                    $server[SParams::CONVARS]['TimeLeft'] = t($buffer['TimeLeft']);
                }
                return true;
            }
            default: return false;
        }
    }
}
