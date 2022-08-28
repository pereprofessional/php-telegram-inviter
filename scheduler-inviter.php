<?
require_once('handler.php');
$ml = new MadelineHandler();

$queue = $ml->getSchedulerCreatedStatuses();
echo '$ml->getSchedulerCreatedStatuses():';
echo '<pre>';
var_dump($queue);
echo '</pre>';
echo '<br/><br/>';

if (count($queue) < 1) return;

$response_array = [];
for ($i = 0; $i < 1; $i++) // только один инвайт делаем, так как раз в минуту будет запускаться
{
	array_push(
        $response_array, [
            'response' =>
                $ml->inviteToChannel(
                    $queue[$i]['session_name'],
                    $queue[$i]['participant_username'],
                    $queue[$i]['target_channel_username'],
                    false,
                    $queue[$i]['participant_id']
                ),
            'participant_id' => $queue[$i]['participant_id'],
            'target_channel_username' => $queue[$i]['target_channel_username'],
            'session_name' => $queue[$i]['session_name']
        ]
    );
	$ml->updateSchedulerStatusById($queue[$i]['id']);
}
echo '$response_array:';
echo '<pre>';
var_dump($response_array);
echo '</pre>';
echo '<br/><br/>';

?>