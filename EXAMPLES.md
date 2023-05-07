## Example #1

```php
require_once __DIR__.'/vendor/autoload.php';

use ZekyWolf\LGSQ\{
    LGSQ,
    Helpers\ERequestParams,
    Helpers\EServerParams,
    Helpers\EConnectionParams,
    Helpers\Games
};

$lgsq = new LGSQ(
    Games::URBANTERROR,
    [
        EConnectionParams::IP => '176.9.28.206', 
        EConnectionParams::PORT => 27971, 
        EConnectionParams::QPORT => 27971
    ],
    [
        ERequestParams::SERVER, 
        ERequestParams::CONVARS, 
        ERequestParams::PLAYERS
    ]
);

// Now you can access via one of the methods from class
$lgsq->getData();           // <- This will return all server data;
$lgsq->getBasicData();      // <- This will return basic data;
$lgsq->getServerData();     // <- This will return server data;
$lgsq->getPlayers();        // <- This will return players data;
$lgsq->getTeams();          // <- This will return teams data;
$lgsq->getConvars();        // <- This will return convars data;
$lgsq->getCustomData();     // <- This will return custom data;

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

$lgsq = new LGSQ(
    Games::DISCORD,
    [
        EConnectionParams::IP => 'nDuNTC6'
    ],
    [
        ERequestParams::SERVER, 
        ERequestParams::CONVARS, 
        ERequestParams::PLAYERS
    ]
);

$result = $lgsq->getData();

echo 'Status: '.($result[EServerParams::BASIC]['status'] == 1 ? 'ONLINE' : 'OFFLINE').'<br />';
echo 'Name: '.$result[EServerParams::SERVER]['name'].'<br />';
echo 'Map: '.$result[EServerParams::SERVER]['map'] . '<br />';
echo 'Players: '.$result[EServerParams::SERVER]['players'].'/'.$result[EServerParams::SERVER]['playersmax'].'<br />';
```
