## Example #1

```php
require_once __DIR__.'/LGSQ/vendor/autoload.php';

use ZekyWolf\LGSQ\LGSQ;
use ZekyWolf\LGSQ\Helpers\ERequestParams as RParams;
use ZekyWolf\LGSQ\Helpers\EServerParams as SParams;

$lgsq = new LGSQ('urbanterror', '176.9.28.206', 27971, 27971,
    [
        RParams::SERVER, 
        RParams::CONVARS, 
        RParams::PLAYERS
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

echo 'Status: '.($result[SParams::BASIC]['status'] == 1 ? 'ONLINE' : 'OFFLINE').'<br />';
echo 'Name: '.$result[SParams::SERVER]['name'].'<br />';
echo 'Map: '.$result[SParams::SERVER]['map'] . '<br />';
echo 'Players: '.$result[SParams::SERVER]['players'].'/'.$result[SParams::SERVER]['playersmax'].'<br />';

```

## Example #2

```php
require_once __DIR__.'/vendor/autoload.php';

$lgsq = new LGSQ('discord', 'nDuNTC6', 1, 1, 
    [
        RParams::SERVER, 
        RParams::CONVARS, 
        RParams::PLAYERS
    ]
);

$result = $lgsq->getData();

echo 'Status: '.($result[SParams::BASIC]['status'] == 1 ? 'ONLINE' : 'OFFLINE').'<br />';
echo 'Name: '.$result[SParams::SERVER]['name'].'<br />';
echo 'Map: '.$result[SParams::SERVER]['map'] . '<br />';
echo 'Players: '.$result[SParams::SERVER]['players'].'/'.$result[SParams::SERVER]['playersmax'].'<br />';
```
