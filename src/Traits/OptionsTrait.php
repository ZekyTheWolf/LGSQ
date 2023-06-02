<?php

namespace ZekyWolf\LGSQ\Traits;

use ZekyWolf\LGSQ\Params\EOptionsParams as OParams;

trait OptionsTrait
{
    private static array $options = [
        OParams::CURL_CONNECT_TIMEOUT => 1,
        OParams::CURL_TIMEOUT => 3,
        OParams::STREAM_BLOCKING => true,
        OParams::STREAM_TIMEOUT => 3,
    ];

    /**
     * Set param option
     *
     * @param string $key
     * @param string|int|array $value
     *
     * @return void
     */
    public static function setOption(string $key, string|int|array $value): void
    {
        self::$options[$key] = $value;
    }

    /**
     * Get param option
     *
     * @param string $key
     *
     * @return string|int|array
     */
    public static function getOption(string $key): string|int|array
    {
        return self::$options[$key];
    }
}
