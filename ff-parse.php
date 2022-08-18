<?
// НЕ ЗАПУСКАТЬ. иначе табла participants заполнится повторами
// стоит лимит 1000 сек, но nginx пишет ошибку по таймауту спустя ~850 записей у меня на локале
require_once('../config.php');

require_once('MadelineHandler.php');

set_time_limit(1000);

$ml = new MadelineHandler();

// заходим по юзером
$ml->init('vaso');

// вытягиваем инфу о исходном канале
$response = $ml->getPwrChat('idealMining');



$z = 0;
foreach ($response['participants'] as $key => $value)
{
    $data = [
        'telegram_source' => $response['username'], // int
        'telegram_source_title' => $response['title'], // varchar
        'telegram_source_type' => $response['type'], // varchar
        'telegram_source_can_view_participants' => $response['can_view_participants'], // varchar
        'telegram_source_participants_count' => $response['participants_count'], // int
        'target_channel_id' => 4, // int
        'invited' => 0, // tinyint
        'user_id' => -1,
        'user_type' => '',
        'user_username' => '',
        'user_first_name' => '',
        'user_last_name' => '',
        'user_verified' => -1,
        'user_restricted' => -10,
        'user_status_sign' => '',
        'user_status_was_online' => -1,
        'user_access_hash' => -1,
        'user_phone' => '',
        'user_bot_nochats' => -1,
        'date' => -1,
        'role' => '',
    ];

    if (isset($value['user']['id'])) $data['user_id'] = $value['user']['id']; // int
    if (isset($value['user']['type'])) $data['user_type'] = $value['user']['type']; // varchar
    if (isset($value['user']['username'])) $data['user_username'] = $value['user']['username']; // varchar
    if (isset($value['user']['first_name'])) $data['user_first_name'] = $value['user']['first_name']; // varchar
    if (isset($value['user']['last_name'])) $data['user_last_name'] = $value['user']['last_name']; // varchar
    if (isset($value['user']['verified'])) $data['user_verified'] = $value['user']['verified']; // tinyint
    if (isset($value['user']['restricted'])) $data['user_restricted'] = $value['user']['restricted']; // tinyint
    if (isset($value['user']['status']['_'])) $data['user_status_sign'] = $value['user']['status']['_']; // varchar
    if (isset($value['user']['status']['was_online'])) $data['user_status_was_online'] = $value['user']['status']['was_online']; // int
    if (isset($value['user']['access_hash'])) $data['user_access_hash'] = $value['user']['access_hash']; // int
    if (isset($value['user']['phone'])) $data['user_phone'] = $value['user']['phone']; // varchar
    if (isset($value['user']['user_bot_nochats'])) $data['user_bot_nochats'] = $value['user']['user_bot_nochats']; // tinyint
    if (isset($value['date'])) $data['date'] = $value['date']; // int
    if (isset($value['role'])) $data['role'] = $value['role']; // varchar



    //var_dump($data);
    //echo "\r\n\r\n\r\n\r\n\r\n";

    //insertToDb($data);


    $z++;
    //if ($z == 3) break;
}

function insertToDb($data)
{
    $mysql_connection = DB::DBconnect();
    mysqli_set_charset($mysql_connection, "utf8mb4");
    $query = "INSERT INTO `participants` (
                
                `telegram_source`,
                `telegram_source_title`,
                `telegram_source_type`,
                `telegram_source_can_view_participants`,
                `telegram_source_participants_count`,
                `target_channel_id`,
                `invited`,
                `user_id`,
                `user_type`,
                `user_username`,
                `user_first_name`,
                `user_last_name`,
                `user_verified`,
                `user_restricted`,
                `user_status_sign`,
                `user_status_was_online`,
                `user_access_hash`,
                `user_phone`,
                `user_bot_nochats`,
                `date`,
                `role`
                ) VALUES (
                '".$data['telegram_source']."',
                '".$data['telegram_source_title']."',
                '".$data['telegram_source_type']."',
                '".$data['telegram_source_can_view_participants']."',
                '".$data['telegram_source_participants_count']."',
                '".$data['target_channel_id']."',
                '".$data['invited']."',
                '".$data['user_id']."',
                '".$data['user_type']."',
                '".$data['user_username']."',
                '".$data['user_first_name']."',
                '".$data['user_last_name']."',
                '".$data['user_verified']."',
                '".$data['user_restricted']."',
                '".$data['user_status_sign']."',
                '".$data['user_status_was_online']."',
                '".$data['user_access_hash']."',
                '".$data['user_phone']."',
                '".$data['user_bot_nochats']."',
                '".$data['date']."',
                '".$data['role']."'
                )";
    mysqli_query($mysql_connection,$query);
}


?>