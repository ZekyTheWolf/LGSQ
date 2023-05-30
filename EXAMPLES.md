## Example #1

```php
require_once __DIR__.'/vendor/autoload.php';

use ZekyWolf\LGSQ\{
    LGSQ,
    Helpers\ERequestParams,
    Helpers\EServerParams,
    Helpers\EConnectionParams,
    Helpers\Protocols
};

$lgsq = new LGSQ(
    // Connection params
    [
        EConnectionParams::TYPE => Protocols::URBANTERROR,
        EConnectionParams::IP => '176.9.28.206', 
        EConnectionParams::PORT => 27971, 
        EConnectionParams::QPORT => 27971
    ],

    // Request params
    // Default set are those listed below.
    // If you want overwrite them, pass one of the params from ERequestParamss class.
    [
        ERequestParams::SERVER, 
        ERequestParams::CONVARS, 
        ERequestParams::PLAYERS
    ],

    // Custom params
    [
        'test' => 'Custom parameter assigned to this server'
    ]
);

// Now you can access via one of the methods from class
$lgsq->getData();           // <- All server data (basic, server, players, teams, convars, ... )
$lgsq->getBasicData();      // <- Basic server data (ip, port, qport, game, scheme connection type, ...)
$lgsq->getServerData();     // <- Server data (players, playersmax, status, map, link, ...)
$lgsq->getPlayers();        // <- Players (id, name, score, ...)
$lgsq->getTeams();          // <- Teams (name, score, ...)
$lgsq->getConvars();        // <- Convars (all custom convars provided from server...)
$lgsq->getCustomData();     // <- Custom data created by you...

// We will take all data
$result = $lgsq->getData();

echo 'Status: '.($result[EServerParams::BASIC]['status'] == 1 ? 'ONLINE' : 'OFFLINE').'<br />';
echo 'Name: '.$result[EServerParams::SERVER]['name'].'<br />';
echo 'Map: '.$result[EServerParams::SERVER]['map'] . '<br />';
echo 'Players: '.$result[EServerParams::SERVER]['players'].'/'.$result[EServerParams::SERVER]['playersmax'].'<br />';

```

## Example #2

```php
require_once __DIR__.'/vendor/autoload.php';

$lgsq = new LGSQ([
    EConnectionParams::TYPE => Protocols::DISCORD,
    EConnectionParams::IP => 'nDuNTC6'
]);

$result = $lgsq->getData();

echo 'Status: '.($result[EServerParams::BASIC]['status'] == 1 ? 'ONLINE' : 'OFFLINE').'<br />';
echo 'Name: '.$result[EServerParams::SERVER]['name'].'<br />';
echo 'Map: '.$result[EServerParams::SERVER]['map'] . '<br />';
echo 'Players: '.$result[EServerParams::SERVER]['players'].'/'.$result[EServerParams::SERVER]['playersmax'].'<br />';
```
