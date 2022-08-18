<?
include 'config.php';

$mysql_constants = DB::showBDConstants();

$mysql_connection = mysqli_connect($mysql_constants['ip'], $mysql_constants['user'], $mysql_constants['pass'], $mysql_constants['db']);

$sessionsFromMysql = mysqli_query($mysql_connection, "SELECT * FROM inviter_sessions")->fetch_all(MYSQLI_ASSOC);

echo '<pre>';
var_dump($sessionsFromMysql);
echo '</pre>';
?>