<?
var_dump(scandir(dirname(__FILE__)));
echo '<hr/>';
echo dirname(__FILE__);
return;
include dirname(__FILE__).'/mp/vendor/autoload.php';

if (!isset($_GET['session'])) { echo 'Надо передать GET-параметр "session" с названием сессии'; return; }

$MadelineProto = new \danog\MadelineProto\API('sessions/session.'.$_GET['session']);
$MadelineProto->start();

$me = $MadelineProto->getSelf();

$MadelineProto->logger($me);

echo '<pre>';
var_dump($me);
echo '</pre>';

require_once(dirname(__FILE__).'/handler.php');
$ml = new MadelineHandler();
$ml->refreshProfileInfo($_GET['session']);


?>