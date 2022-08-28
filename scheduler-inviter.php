<?
flog('', true);

require_once('handler.php');
$ml = new MadelineHandler();

$queue = $ml->getSchedulerCreatedStatuses();
echo '$ml->getSchedulerCreatedStatuses():';
echo '<pre>';
var_dump($queue);
echo '</pre>';
echo '<br/><br/>';

if (count($queue) < 1) 
{ 
    flog('There is no queue in scheduele: queue='.count($queue).'.');
    return;
}

flog('Got queue from scheduele ('.count($queue).'): ');
$c = 1;
foreach ($queue as $key => $q) 
{
    flog('#'.$c.' — session_name: '.$q['session_name'].'; participant_id: '.$q['participant_id'].'; target_channel_username: '.$q['target_channel_username'].'.');
    $c++;
}

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

flog('Got invites ('.count($response_array).'):');

$c = 1;
foreach ($response_array as $key => $a) 
{
    flog('#'.$c.' — message: '.$a['response']['message'].'; code: '.$a['response']['code'].'; status: '.$a['response']['status'].'; session_name: '.$a['session_name'].'; participant_id: '.$a['participant_id'].'; target_channel_username: '.$a['target_channel_username'].'.');

    $c++;
}

flog('End of file.');

function flog($text, $notime = false)
{
    $date = date('d.m.y H:i:s', time());
    if ($notime) $string = $text; else $string = '['.$date.']: '.$text;
    $file = fopen('scheduler-inviter.log', 'a');
    fwrite($file, $string.PHP_EOL);
    fclose($file);
}
?>