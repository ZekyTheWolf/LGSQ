<?php

namespace ZekyWolf\LGSQ\Helpers\Parse;

class Escape
{
    public static function get(string $text): string
    {
        $escaped = array('\t', '\v', '\r', '\n', '\f', '\s', '\p', '\/');
        $unescaped = array(' ', ' ', ' ', ' ', ' ', ' ', '|', '/');
        $text = str_replace($escaped, $unescaped, $text);
        return $text;
    }
}
