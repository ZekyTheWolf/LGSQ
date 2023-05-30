<?php

namespace ZekyWolf\LGSQ\Protocols;

use ZekyWolf\LGSQ\Helpers\{
    EServerParams as SParams,
    ERequestParams as RParams,
    EConnectionParams as CParams
};

class Query36
{
    public static function get(&$server, &$lgsl_need, &$lgsl_fp)
    {
        if(!$lgsl_fp) {
            return false;
        }

        $lgsl_need[RParams::SERVER] = false;

        curl_setopt(
            $lgsl_fp,
            CURLOPT_URL,
            "https://discord.com/api/v10/invites/{$server[SParams::BASIC][CParams::IP]}?with_counts=true"
        );

        $buffer = curl_exec($lgsl_fp);

        $buffer = json_decode($buffer, true);

        if (isset($buffer['message'])) {
            $server[SParams::CONVARS]['_error_fetching_info'] = $buffer['message'];
            return false;
        }

        $server[SParams::SERVER]['map'] = 'discord';
        $server[SParams::SERVER]['name'] = self::checkValue($buffer['guild']['name']);
        $server[SParams::SERVER]['players'] = self::checkValue($buffer['approximate_presence_count'], 2);
        $server[SParams::SERVER]['playersmax'] = self::checkValue($buffer['approximate_member_count'], 2);
        $server[SParams::CONVARS]['id'] = self::checkValue($buffer['guild']['id'], 2);

        $server[SParams::CONVARS]['description'] = self::checkValue($buffer['guild']['description']);

        if(isset($buffer['guild']['welcome_screen']) && $buffer['guild']['welcome_screen']['description']) {
            $server[SParams::CONVARS]['description'] = $buffer['guild']['welcome_screen']['description'];
        }

        $server[SParams::CONVARS]['features'] = implode(', ', self::checkValue($buffer['guild']['features']));
        $server[SParams::CONVARS]['nsfw'] = self::checkValue($buffer['guild']['nsfw']);

        if (isset($buffer['inviter'])) {
            $server[SParams::CONVARS]['inviter'] = $buffer['inviter']['username'] . "#" . $buffer['inviter']['discriminator'];
        }

        if ($lgsl_need[RParams::PLAYERS]) {
            $lgsl_need[SParams::PLAYERS] = false;

            curl_setopt(
                $lgsl_fp,
                CURLOPT_URL,
                "https://discordapp.com/api/guilds/{$server[SParams::CONVARS]['id']}/widget.json"
            );
            $buffer = curl_exec($lgsl_fp);
            $buffer = json_decode($buffer, true);

            if (isset($buffer['code']) and $buffer['code'] == 0) {
                $server[SParams::CONVARS]['_error_fetching_users'] = $buffer['message'];
            }

            if (isset($buffer['channels'])) {
                foreach ($buffer['channels'] as $key => $value) {
                    $server[SParams::CONVARS]['channel'.$key] = $value['name'];
                }
            }

            if (isset($buffer['members'])) {
                foreach ($buffer['members'] as $key => $value) {
                    $server[SParams::PLAYERS][$key]['name'] = $value['username'];
                    $server[SParams::PLAYERS][$key]['status'] = $value['status'];
                    $server[SParams::PLAYERS][$key]['game'] = isset($value['game']) ? $value['game']['name'] : '--';
                }
            }
        }

        return true;
    }

    public static function checkValue($value, $type = 1)
    {
        if(isset($value)) {
            return $value;
        }

        switch($type) {
            case 1: { return "Value is undefined"; }
            case 2: { return 0; }
        }
    }
}
