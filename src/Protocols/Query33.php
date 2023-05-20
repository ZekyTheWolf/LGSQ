<?php

namespace ZekyWolf\LGSQ\Protocols;

use ZekyWolf\LGSQ\Helpers\{
    Parse\Escape,
    Parse\ParseString,
    Parse\Time,
    EServerParams as SParams,
    ERequestParams as RParams,
    EConnectionParams as CParams,
};

class Query33
{
    public static function get(&$server, &$lgsl_need, &$lgsl_fp)
    {
        $buffer = fread($lgsl_fp, 4096);
        if ($server[SParams::BASIC]['type'] === 'teaspeak') {
            if (strpos($buffer, 'TeaSpeak') === false && strpos(fread($lgsl_fp, 4096), 'TeaSpeak') === false) {
                return false;
            }
        } else {
            if (strpos($buffer, 'TS') === false) {
                return false;
            }
        }
        $ver = $server[SParams::BASIC]['type'] === 'ts' ? 0 : 1;
        $param[0] = [ 'sel ', 'si', "\r\n", 'pl' ];
        $param[1] = [ 'use port=', 'serverinfo', ' ', 'clientlist -country', 'channellist -topic' ];
        if ($ver) {
            fread($lgsl_fp, 4096);
        }
        fwrite($lgsl_fp, "{$param[$ver][0]}{$server[SParams::BASIC][CParams::PORT]}\n"); // select virtualserver
        if (strtoupper(substr(fread($lgsl_fp, 4096), -4, -2)) != 'OK') {
            return false;
        }

        fwrite($lgsl_fp, "{$param[$ver][1]}\n"); // request serverinfo
        $buffer = fread($lgsl_fp, 4096);
        if (!$buffer || substr($buffer, 0, 5) === 'error') {
            return false;
        }
        while (strtoupper(substr($buffer, -4, -2)) != 'OK') {
            $part = fread($lgsl_fp, 4096);
            if ($part && substr($part, 0, 5) != 'error') {
                $buffer .= $part;
            } else {
                break;
            }
        }

        while ($val = ParseString::get($buffer, 7+7*$ver, $param[$ver][2])) {
            $key = ParseString::get($val, 0, '=');
            $items[$key] = $val;
        }
        if (!isset($items['name'])) {
            return false;
        }
        $server[SParams::SERVER]['name']         = $ver ? Escape::get($items['name']) : $items['name'];
        $server[SParams::SERVER]['map']          = $server[SParams::BASIC]['type'];
        $server[SParams::SERVER]['players']      = intval($items[$ver ? 'clientsonline' : 'currentusers']);
        $server[SParams::SERVER]['playersmax']   = intval($items[$ver ? 'maxclients' : 'maxusers']);
        $server[SParams::SERVER]['password']     = intval($items[$ver ? 'flag_password' : 'password']);
        $server[SParams::CONVARS]['platform']     = $items['platform'];
        $server[SParams::CONVARS]['motd']         = $ver ? Escape::get($items['welcomemessage']) : $items['welcomemessage'];
        $server[SParams::CONVARS]['uptime']       = Time::get($items['uptime']);
        $server[SParams::CONVARS]['banner']       = Escape::get($items['hostbanner_url']);
        $server[SParams::CONVARS]['channelscount']= $items[$ver ? 'channelsonline' : 'currentchannels'];
        if ($ver) {
            $server[SParams::CONVARS]['version'] = Escape::get($items['version']);
        }

        if ($lgsl_need[RParams::PLAYERS] && $server[SParams::SERVER]['players'] > 0) {
            fwrite($lgsl_fp, "{$param[$ver][3]}\n"); // request playerlist
            $buffer = fread($lgsl_fp, 4096);
            while (substr($buffer, -4) != "OK\r\n" && substr($buffer, -2) != "\n\r") {
                $part = fread($lgsl_fp, 4096);
                if ($part && substr($part, 0, 5) != 'error') {
                    $buffer .= $part;
                } else {
                    break;
                }
            }

            $i = 0;
            if ($ver) {
                while ($items = ParseString::get($buffer, 0, '|')) {
                    ParseString::get($items, 0, 'e=');
                    $name = ParseString::get($items, 0, ' ');
                    if (substr($name, 0, 15) === 'Unknown\sfrom\s') {
                        continue;
                    }
                    $server[SParams::PLAYERS][$i]['name'] = Escape::get($name);
                    ParseString::get($items, 0, 'ry');
                    $server[SParams::PLAYERS][$i]['country'] = substr($items, 0, 1) === '=' ? substr($items, 1, 2) : '';
                    $i++;
                }
            } else {
                $buffer = substr($buffer, 89, -4);
                while ($items = ParseString::get($buffer, 0, "\r\n")) {
                    $items = explode("\t", $items);
                    $server[SParams::PLAYERS][$i]['name'] = substr($items[14], 1, -1);
                    $server[SParams::PLAYERS][$i]['ping'] = $items[7];
                    $server[SParams::PLAYERS][$i]['time'] = Time::get($items[8]);
                    $i++;
                }
            }
        }

        if ($lgsl_need[RParams::CONVARS] && $ver) {
            fwrite($lgsl_fp, "{$param[$ver][4]}\n"); // request channellist
            $buffer = fread($lgsl_fp, 4096);
            while (substr($buffer, -4) != "OK\r\n" && substr($buffer, -2) != "\n\r") {
                $part = fread($lgsl_fp, 4096);
                if ($part && substr($part, 0, 5) != 'error') {
                    $buffer .= $part;
                } else {
                    break;
                }
            }
            $server[SParams::CONVARS]['channels'] = '';
            while ($items = ParseString::get($buffer, 0, '|')) {
                $id = str_pad(ParseString::get($items, 4, ' '), 5, '0', STR_PAD_LEFT);
                ParseString::get($items, 0, 'e=');
                $name = ParseString::get($items, 0, ' ');
                if (strpos($name, '*spacer') != false) {
                    continue;
                }
                $server[SParams::CONVARS]['channels'] .= preg_replace("/\[[cr]?spacer[\d\w-]{0,5}\]/", "", Escape::get($name)) . "\n";
            }
        }

        return true;
    }
}
