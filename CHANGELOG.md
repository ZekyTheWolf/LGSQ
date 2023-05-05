# Changelog

All notable changes to `lgsq` will be documented in this file

## 1.0.0 - 05.05.2023

- initial release
- New request method
```php
/**
 * 
 * @param $type         Game type
 * @param $ip           Server IP/Hostname
 * @param $c_port       Connection port
 * @param $q_port       Query Port
 * @param $request      Requested data, 
 *                      valid: 
 *                          > Array string, only those 3 are valid, any others will be ignored
 *                          [ "s", "p", "c" ]
 *                          > Or usage via ERequestParams abastract class:
 *                          [ RParams::SERVER, SParams::PLAYERS, SParams::CONVARS]
 * @param $cdata        Custom data, default []
 * @param $s_port       Server port, default 0
 * 
 * @noreturn
 *
 * Explanation:
 * Since this is rebuild of LGSL to be more compatibile with Laravel and more PHP Frameworks
 * i decided to make few changes, one visible is in $request.
 *
 * Request is now a array of values, you can use directly 's', 'p', 'c' for specified request
 * but i would recommend using abstract class of ERequestParams where are stored params for request
 * this way you can avoid any potential errors.
 */
$lgsq = new LGSQ('<game type>', '<ip>', <connection port>, <query port>,
    [
        RParams::SERVER, 
        RParams::CONVARS, 
        RParams::PLAYERS
    ]
)
```
- PSR4

