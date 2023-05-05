<?php

namespace ZekyWolf\LGSQ\Helpers\Parse;

class Time
{
    public static function get(int|float $seconds): string|int
    {
        if (!$seconds or $seconds === "") {
            return "";
        }

        $n = $seconds < 0 ? "-" : "";

        $seconds = abs($seconds);

        $d = intval($seconds / 86400);
        $h = intval($seconds / 3600) % 24;
        $m = intval($seconds / 60) % 60;
        $s = intval($seconds) % 60;

        $h = str_pad($h, "2", "0", STR_PAD_LEFT);
        $m = str_pad($m, "2", "0", STR_PAD_LEFT);
        $s = str_pad($s, "2", "0", STR_PAD_LEFT);

        return $d > 0 ? "{$d}d {$n}{$h}:{$m}:{$s}" : "{$n}{$h}:{$m}:{$s}";
    }
}
