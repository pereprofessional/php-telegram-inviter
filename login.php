<?
include dirname(__FILE__).'/mp/vendor/autoload.php';

if (!isset($_GET['session'])) { echo 'Надо передать GET-параметр "session" с названием сессии'; return; }

$MadelineProto = new \danog\MadelineProto\API(dirname(__FILE__).'/sessions/session.'.$_GET['session']);
$MadelineProto->start();

return;
$me = $MadelineProto->getSelf();

$MadelineProto->logger($me);

echo '<pre>';
var_dump($me);
echo '</pre>';

require_once(dirname(__FILE__).'/handler.php');
$ml = new MadelineHandler();
$ml->refreshProfileInfo($_GET['session']);

//
?>