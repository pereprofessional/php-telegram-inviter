<?
require_once('../config.php');

require_once('MadelineHandler.php');




$mysql_connection = DB::DBconnect();
mysqli_set_charset($mysql_connection, "utf8mb4");
// LIMIT 2, потому что у нас 2 спам-акка. раз в час в кроне будет запускать этот файл и 2 спам-акка будут по 1 приглашению делать (итого 2 приглоса в час)
$req = mysqli_query($mysql_connection, "SELECT * FROM participants WHERE invited = 0 AND role != 'admin' AND role != 'creator' AND user_username != '' LIMIT 2");

$usersToInvite = [];

$z = 0;
while ($row = mysqli_fetch_array($req, MYSQLI_BOTH))
{
    //if (($row['role'] == 'admin') || ($row['role'] == 'creator')) continue; // отбрасываем админа и создателя беседы из инвайта
    //if ($row['invited'] == 1) continue; // пропускаем если уже был заинвайчен (это условие продублировано в запросе к мускулу)
    array_push($usersToInvite, $row);
    //var_dump($row);
    //echo "\r\n\r\n\r\n\r\n";
    $z++;
}
//echo $z;

var_dump($usersToInvite[0]);


$ml = new MadelineHandler();

$r1 = mt_rand(0, 1);
$r2 = mt_rand(0, 1);

var_dump('$r1:'.$r1);
var_dump('$r2:'.$r2);

if ($r1 == 1)
{
    try
    {
        echo '!a';
        $ml->init('test3');
        $Update = $ml->inviteToChannel("https://t.me/baikal_miners", [ '@'.$usersToInvite[0]['user_username'] ]);
        //var_dump($Update);

        sleep(1);
        $me = $ml->getSelf();
        sleep(1);
        $messages = $ml->getHistory([
            'peer' => 'baikal_miners',
            'offset_id' => 0,
            'offset_date' => 0,
            'add_offset' => 0,
            'limit' => 10,
            'max_id' => 0,
            'min_id' => 0,
            'hash' => 0
        ]);
        //var_dump($messages); echo '###';
        sleep(1);
        $messageIdsToDelete = [];
        foreach ($messages['messages'] as $key => $value)
        {
            if (isset($messages['messages'][$key]['from_id']['user_id']))
            {
                if (isset($messages['messages'][$key]['action']['_']))
                {
                    if (($messages['messages'][$key]['from_id']['user_id'] == $me['id']) && ($messages['messages'][$key]['action']['_'] == 'messageActionChatAddUser'))
                    {
                        array_push($messageIdsToDelete, $messages['messages'][$key]['id']);
                        $res = $ml->deleteMessages(['channel' => 'baikal_miners', 'id' => [ $messages['messages'][$key]['id'] ]]); // удаляем сообщение с этим id
                        //var_dump($messages['messages'][$key]['id']);
                        sleep(1);
                    }
                }
            }
        }

        echo '!end';

    }
    catch (\danog\MadelineProto\Exception $e)
    {
        echo '!b';
        var_dump($e->getMessage());
    }
    mysqli_query($mysql_connection,"UPDATE `participants` SET `invited` = 1 WHERE `id` = '".$usersToInvite[0]['id']."';");
}


echo "\r\n\r\n\r\n".'<br/><br/><br/>starting next init...<br/><br/><br/>'."\r\n\r\n\r\n";
sleep(5);

if ($r2 == 1)
{
    try
    {
        echo '!c';
        $ml->init('test4');
        $Update = $ml->inviteToChannel("baikal_miners", [ '@'.$usersToInvite[1]['user_username'] ]);
        //var_dump($Update);

        sleep(1);
        $me = $ml->getSelf();
        sleep(1);
        $messages = $ml->getHistory([
            'peer' => 'baikal_miners',
            'offset_id' => 0,
            'offset_date' => 0,
            'add_offset' => 0,
            'limit' => 10,
            'max_id' => 0,
            'min_id' => 0,
            'hash' => 0
        ]);
        //var_dump($messages); echo '###';
        sleep(1);
        $messageIdsToDelete = [];
        foreach ($messages['messages'] as $key => $value)
        {
            if (isset($messages['messages'][$key]['from_id']['user_id']))
            {
                if (isset($messages['messages'][$key]['action']['_']))
                {
                    if (($messages['messages'][$key]['from_id']['user_id'] == $me['id']) && ($messages['messages'][$key]['action']['_'] == 'messageActionChatAddUser'))
                    {
                        array_push($messageIdsToDelete, $messages['messages'][$key]['id']);
                        $res = $ml->deleteMessages(['channel' => 'baikal_miners', 'id' => [ $messages['messages'][$key]['id'] ]]); // удаляем сообщение с этим id
                        //var_dump($messages['messages'][$key]['id']);
                        sleep(1);
                    }
                }
            }
        }

        echo '!end';
    }
    catch (\danog\MadelineProto\Exception $e)
    {
        echo '!d';
        var_dump($e->getMessage());
    }
    mysqli_query($mysql_connection,"UPDATE `participants` SET `invited` = 1 WHERE `id` = '".$usersToInvite[1]['id']."';");
}



/*
$ml = new MadelineHandler();
$ml->init($_GET['session']);
$res = $ml->inviteToChannel("baikal_miners", [ $_GET['user_id'] ]);
var_dump($res);*/


/*
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.'.$_GET['session']);

$MadelineProto->start();
//$me = $MadelineProto->getSelf();
//var_dump($me);
//$MadelineProto->logger($me);

//$MadelineProto->updateSettings($settings);
try
{
    $Update  = $MadelineProto->channels->inviteToChannel(['channel' => 'https://t.me/baikal_miners', 'users' => [ $_GET['user_id'] ], ]);
    var_dump($Update);
}
catch (\danog\MadelineProto\Exception $e)
{
    var_dump($e->getMessage());
}*/

/*include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.vaso');
$MadelineProto->start();

$MadelineProto->settings['peer']['full_fetch'] = true;
$MadelineProto->settings['peer']['cache_all_peers_on_startup'] = true;
$MadelineProto->settings['peer']['full_fetch'] = true;
$MadelineProto->settings['peer']['cache_all_peers_on_startup'] = true;
$MadelineProto->settings['pwr']['requests'] = false;
$MadelineProto->settings['updates']['handle_old_updates'] = false;

$me = $MadelineProto->getSelf();

if (!$me['bot'])
{
    try
    {
        echo '1';
        $Update  = $MadelineProto->channels->inviteToChannel(['channel' => 'https://t.me/baikal_miners', 'users' => [ 1824895431 ], ]);
        //var_dump($res);
    }
    catch (\danog\MadelineProto\Exception $e)
    {
        echo '2';
        var_dump($e->getMessage());
    }
}

var_dump($me);*/

?>