<?php

namespace ZekyWolf\LGSQ\Helpers\Parse;

class Pascal
{
    public static function get(
        string &$buffer,
        int $start_byte = 1,
        int $length_adjust = 0,
        int $end_byte = 0
    ): string {
        $length = ord(substr($buffer, 0, $start_byte)) + $length_adjust;
        $string = substr($buffer, $start_byte, $length);
        $buffer = substr($buffer, $start_byte + $length + $end_byte);

        return $string;
    }
}
