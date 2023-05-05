<?php

namespace ZekyWolf\LGSQ\Helpers\Parse;

class Unpack
{
    public static function get(string $string, string $format)
    {
        list(, $string) = @unpack($format, $string);

        return $string;
    }
}
