<?php

namespace ZekyWolf\LGSQ\Helpers\Parse;

class ParseString
{
    public static function get(string &$buffer, int $start_byte = 0, string $end_marker = "\x00"): string
    {
        $buffer = substr($buffer, $start_byte);
        $length = strpos($buffer, $end_marker);

        if ($length === false) {
            $length = strlen($buffer);
        }

        $string = substr($buffer, 0, $length);
        $buffer = substr($buffer, $length + strlen($end_marker));

        return $string;
    }
}
