<?php

namespace ZekyWolf\LGSQ\Helpers\Parse;

class Byte
{
    public static function get(string &$buffer, int $length): string
    {
        $string = substr($buffer, 0, $length);
        $buffer = substr($buffer, $length);

        return $string;
    }
}
