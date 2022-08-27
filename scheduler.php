<?
flog('', true);


require_once('handler.php');
$ml = new MadelineHandler();


// если в inviter_schedule есть хоть одна запись с status=0, то отмена планировщика
$res = $ml->getSchedulerCreatedStatuses();
echo '$ml->getSchedulerCreatedStatuses():';
echo '<pre>';
var_dump($res);
echo '</pre>';
echo '<br/><br/>';

if (count($res) > 0)
{
	flog('There is more than 1 status=0 in inviter_schedule: '.count($res).'.');
	return;
}


// ловим линки
$links = $ml->getLinksForCron();
echo '$ml->getLinksForCron():';
echo '<pre>';
var_dump($links);
echo '</pre>';
echo '<br/><br/>';

if (count($links) > 1)
{
	flog('There is more than 1 link: '.count($links).'. There is no setup for multilinks yet.');
	return;
}

flog('Got link: '.$links[0]['tg_src_username'].' to '.$links[0]['ch_username'].'. ');


// ловим сессии
$sessions = $ml->getSessionsForCron(); // сделать проверку на спам блок как-то
echo '$ml->getSessionsForCron():';
echo '<pre>';
var_dump($sessions);
echo '</pre>';
echo '<br/><br/>';

if (count($sessions) < 1)
{
	flog('There is less than 1 session found: '.count($sessions).'. Try to turn any on.');
	return;
}

$sessionsName = '';
foreach ($sessions as $key => $session) 
{
	$sessionsName .= $session['session_name'];
	if ($key < count($sessions) - 1) $sessionsName .= ', ';
}
flog('Got sessions ('.count($sessions).'): '.$sessionsName.'. ');


// получаем участников для инвайта. их кол-во совпадает с кол-вом сессий
$participants = $ml->getParticipantsForCron($links[0]['id'], count($sessions));
echo '$ml->getParticipantsForCron($link_id, $limit):';
echo '<pre>';
var_dump($participants);
echo '</pre>';
echo '<br/><br/>';

if (count($participants) < count($sessions))
{
	flog('There is less participants than sessions. Participants: '.count($participants).', sessions: '.count($sessions).'. ');
	return;
}

$participantsName = '';
foreach ($participants as $key => $participant) 
{
	$participantsName .= $participant['id'].':'.$participant['user_username'];
	if ($key < count($participants) - 1) $participantsName .= ', ';
}
flog('Got participants ('.count($participants).'): '.$participantsName.'. ');


// формируем массив очереди для планировщика
$queue = [];
for ($i = 0; $i < count($sessions); $i++)
{
	array_push($queue, [
		'target_channel_id' => $links[0]['ch_id'],
		'target_channel_username' => $links[0]['ch_username'],
		'session_id' => $sessions[$i]['id'],
		'session_name' => $sessions[$i]['session_name'],
		'participant_id' => $participants[$i]['id'],
		'participant_username' => $participants[$i]['user_username'],
		'status' => 0
	]);
}
$res = $ml->addQueueToScheduler($queue);
echo '$queue:';
echo '<pre>';
var_dump($queue);
echo '</pre>';
echo '<br/><br/>';

flog('Inserted queue rows: '.$res.'. ');




function flog($text, $notime = false)
{
	$date = date('d.m.y H:i:s', time());
	if ($notime) $string = $text; else $string = '['.$date.']: '.$text;
	$file = fopen('scheduler.log', 'a');
	fwrite($file, $string.PHP_EOL);
	fclose($file);
}
?>