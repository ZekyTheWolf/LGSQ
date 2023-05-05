<?php

namespace ZekyWolf\LGSQ\Helpers\Parse;

class Colors
{
    public static function get($string, $type)
    {
        switch ($type) {
            case '1':
                $string = preg_replace("/\^x.../", '', $string);
                $string = preg_replace("/\^./", '', $string);

                $string_length = strlen($string);
                for ($i = 0; $i < $string_length; $i++) {
                    $char = ord($string[$i]);
                    if ($char > 160) {
                        $char = $char - 128;
                    }
                    if ($char > 126) {
                        $char = 46;
                    }
                    if ($char == 16) {
                        $char = 91;
                    }
                    if ($char == 17) {
                        $char = 93;
                    }
                    if ($char < 32) {
                        $char = 46;
                    }
                    $string[$i] = chr($char);
                }
                break;

            case '2':
                $string = preg_replace("/\^[\x20-\x7E]/", '', $string);
                break;

            case 'doomskulltag':
                $string = preg_replace('/\\x1c./', '', $string);
                break;

            case 'farcry':
                $string = preg_replace("/\\$\d/", '', $string);
                break;

            case 'fivem':
                $string = preg_replace("/\^\d/", '', $string);
                break;

            case 'painkiller':
                $string = preg_replace('/#./', '', $string);
                break;

            case 'quakeworld':
                $string_length = strlen($string);
                for ($i = 0; $i < $string_length; $i++) {
                    $char = ord($string[$i]);
                    if ($char > 141) {
                        $char = $char - 128;
                    }
                    if ($char < 32) {
                        $char = $char + 30;
                    }
                    $string[$i] = chr($char);
                }
                break;

            case 'savage':
                $string = preg_replace("/\^[a-z]/", '', $string);
                $string = preg_replace("/\^[0-9]+/", '', $string);
                $string = preg_replace("/lan .*\^/U", '', $string);
                $string = preg_replace("/con .*\^/U", '', $string);
                break;

            case 'swat4':
                $string = preg_replace("/\[c=......\]/Usi", '', $string);
                break;

            case 'minecraft':
                $string = preg_replace("/[�§]\w/S", '', $string);
                break;

            case 'factorio':
                $string = preg_replace("/\[[-a-z=0-9\#\/\.,\s?]*\]/S", '', $string);
                break;
        }

        return $string;
    }
}
