<?php
echo 'example: php cmd.php --action=parse_participants --session_name=test1 --link_id=1'."\r\n\r\n";
$val = getopt(null, ["action:", "session_name:", "link_id:"]);
if ($val !== false) 
{
    $action = $val['action'];
    if ($action == 'parse_participants')
    {
        require_once('handler.php');
        $ml = new MadelineHandler();
        $session_name = $val['session_name'];
        $link_id = $val['link_id'];

        $response = $ml->parseParticipants($session_name, $link_id);

        var_dump($response);
    }
}
else 
{
	echo "Could not get value of command line option\n";
}
?>