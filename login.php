<?
include 'mp/vendor/autoload.php';

if (!isset($_GET['session'])) { echo 'Надо передать GET-параметр "session" с названием сессии'; return; }

$MadelineProto = new \danog\MadelineProto\API('sessions/session.'.$_GET['session']);
$MadelineProto->start();

$me = $MadelineProto->getSelf();

$MadelineProto->logger($me);

echo '<pre>';
var_dump($me);
echo '</pre>';

require_once('handler.php');
$ml = new MadelineHandler();
$ml->refreshProfileInfo($_GET['session']);


?>