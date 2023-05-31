# Changelog

All notable changes to `lgsq` will be documented in this file

## 1.4.0 - 30.5.2023
* Seperated params from Helpers folder and moved to own
* Created setOption/getOption function to be able set custom curl timeout.
* Created options abstract class to set available options
* EXAMPLES.md update

## 1.3.0 - 30.5.2023
* Removed property $type from __construct in LGSQ class.
* Created new checking function for TYPE/IP
* Renamed class Games as Protocols since it is more convenient name for it.
* Renamed class GameTypeScheme as ProtocolsTypeScheme since it is more convenient name for it.
* Examples update
* Code format

## 1.2.0 - 26.5.2023
* Release

## 1.1.4-3 - 25.5.2023
* Set default params in LGSQ class when created request.
* [EXAMPLES](./EXAMPLES.md) update.
* Fix Query11, removed old props.
* Fix Query16, removed old props.
* Fix Query30, removed old props.
* Removed old examples from docs to avoid confussion.

## 1.1.4-2 - 21.5.2023
* Moved game type to server data
* Removed old static params (c_port, q_port, type)
* Example update
* Discord api update to v10

## 1.1.4 - 20.5.2023
* Fix Query36 Undefined array key "welcome_screen".
* Fix Query36 when rate limited, error code 1015.
* Fix Query05 remove old param (e)
* Fix Query33 remove old param (c_port)

## 1.1.3 - 15.05.2023
* Bug fix
-> in_array() changed to isset()
* Removed version from composer.json

## 1.1.2 - 06.05.2023
* Removed ip, c_port, q_port, s_port
-> Now you will use array instead of directly params, reason behind this is that you dont need to now setup unnecessary params, checkout our [examples](EXAMPLES.md)
+ Created games class (Games::<game to query>).
+ Created Connection params, recommend using when setting up server.
* Custom data moved directly in to server params, can be retrive via function getCustomData()
* Edited SofwareLink class to use Games class directly instead of string
* Edited GameTypeScheme class to use Games class directly instead of string
- Small code changes, edited examples, readme.

## 1.0.0 - 05.05.2023

- initial release
- New request method
- PSR4

