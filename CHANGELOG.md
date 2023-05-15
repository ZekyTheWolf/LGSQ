# Changelog

All notable changes to `lgsq` will be documented in this file

## 1.1.3 - 15.05.2023
* Bug fix
-> in_array() changed to isset()
* Removed version from composer.json

## 1.1.2 - 06.05.2023
* Removed ip, c_port, q_port, s_port
-> Now you will use array instead of directly params, reason behind this is that you dont need to now setup unnecessary params, checkout our [examples](EXAMPLES.md)
```php
$lgsq = new LGSQ(
    Games::<game>,
    [
        EConnectionParams::IP => 'ip/hostname',
        EConnectionParams::PORT => <server port>,    // ->
        EConnectionParams::QPORT => <query port>,    // -> If not provided, automaticly set to 1
        EConnectionParams::SPORT => <server port>    // ->
    ],
    [
        ERequestParams::SERVER, 
        ERequestParams::CONVARS, 
        ERequestParams::PLAYERS
    ]
)
```
+ Created games class (Games::<game to query>).
+ Created Connection params, recommend using when setting up server.
* Custom data moved directly in to server params, can be retrive via function getCustomData()
* Edited SofwareLink class to use Games class directly instead of string
* Edited GameTypeScheme class to use Games class directly instead of string
- Small code changes, edited examples, readme.

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
        ERequestParams::SERVER, 
        ERequestParams::CONVARS, 
        ERequestParams::PLAYERS
    ]
)
```
- PSR4

