<?
/*$time_start = microtime(true); 
require_once('handler.php');
$ml = new MadelineHandler();

$links = $ml->getLinksForCron();
echo '$ml->getLinksForCron():';
echo '<pre>';
//var_dump($links);
echo '</pre>';
echo '<br/><br/>';

$sessions = $ml->getSessionsForCron();
echo '$ml->getSessionsForCron():';
echo '<pre>';
//var_dump($sessions);
echo '</pre>';
echo '<br/><br/>';

if (count($links) == 1) // если один акк залинкован, тогда простое распределение
{
    echo '$ml->simpleAlgo2():';
    echo '<pre>';
    var_dump(simpleAlgo2());
    echo '</pre>';
    echo '<br/><br/>';
}
else // если больше 1 пары залинковано, то ещё не придумал как распределять ботов/сессии
{

}

function simpleAlgo2()
{
	$time_start = microtime(true); 
    global $links;
    global $sessions;
    global $ml;

    $participants = $ml->getParticipantsForCron($links[0]['id'], count($sessions));
    echo '$ml->getParticipantsForCron($link_id, $limit):';
    echo '<pre>';
    var_dump($participants);
    echo '</pre>';
    echo '<br/><br/>';


    $fp = fopen('v2-log.txt', 'a');
    $msg = date('d-m-Y h:i:s');
	fwrite($fp, PHP_EOL.PHP_EOL.'['.$msg.']: '.PHP_EOL);


    $reqs = [];
    echo '<br/><br/>foreach sessions: <br/><br/>';
    foreach ($sessions as $key => $value)
    {
        //if ($sessions[$key]['session_name'] != 'test4') continue;
        $reqs[] = [
            'session_name' => $sessions[$key]['session_name'],
            'invitee' => [
                'id' => $participants[$key]['id'],
                'link_id' => $participants[$key]['src_ch_id']
            ],
            'channel' => $links[0]['ch_username'],
            'invite_by_internal_id' => true,
        ];
    }
    var_dump($reqs);
    var_dump($ml->multipleInviteToChannel($reqs));

}






echo '<pre>';
$time_end = microtime(true);
print '<hr/>';
print formatBytes(memory_get_peak_usage());
print '<br/>';
$execution_time = ($time_end - $time_start);
print $execution_time.' sec';
echo '</pre>';





function formatBytes($bytes, $precision = 2) 
{
    $units = array("b", "kb", "mb", "gb", "tb");

    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    $bytes /= (1 << (10 * $pow));

    return round($bytes, $precision) . " " . $units[$pow];
}




return;*/




require_once('handler.php');
$ml = new MadelineHandler();

$links = $ml->getLinksForCron();
echo '$ml->getLinksForCron():';
echo '<pre>';
var_dump($links);
echo '</pre>';
echo '<br/><br/>';

$sessions = $ml->getSessionsForCron();
echo '$ml->getSessionsForCron():';
echo '<pre>';
var_dump($sessions);
echo '</pre>';
echo '<br/><br/>';

if (count($links) == 1) // если один акк залинкован, тогда простое распределение
{
    echo '$ml->simpleAlgo():';
    echo '<pre>';
    var_dump(simpleAlgo());
    echo '</pre>';
    echo '<br/><br/>';
}
else // если больше 1 пары залинковано, то ещё не придумал как распределять ботов/сессии
{

}

function simpleAlgo()
{
    global $links;
    global $sessions;
    global $ml;

    $participants = $ml->getParticipantsForCron($links[0]['id'], count($sessions));
    echo '$ml->getParticipantsForCron($link_id, $limit):';
    echo '<pre>';
    var_dump($participants);
    echo '</pre>';
    echo '<br/><br/>';

    $response_array = [];
    foreach ($sessions as $key => $value)
    {
        $rand = mt_rand(0, 1); $rand = 1;
        if ($rand == 1)
        {
            array_push(
                $response_array, [
                    'response' =>
                        $ml->inviteToChannel(
                            $sessions[$key]['session_name'],
                            [
                                'id' => $participants[$key]['id'],
                                'link_id' => $participants[$key]['src_ch_id']
                            ],
                            $links[0]['ch_username'],
                            true
                        ),
                    'participant_id' => $participants[$key]['id'],
                    'link_id' => $participants[$key]['src_ch_id'],
                    'session_name' => $sessions[$key]['session_name']
                ]
            );
        }
    }
    return $response_array;
}

?>